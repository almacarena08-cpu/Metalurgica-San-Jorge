package modulos.common;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.DataOutputStream;
import java.io.ByteArrayOutputStream;
import java.net.HttpURLConnection;
import java.net.CookieManager;
import java.net.CookieStore;
import java.net.CookiePolicy;
import java.net.CookieHandler;
import java.net.URI;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Map;
import java.util.List;

public class ApiClient {
    public static final String API_BASE = System.getProperty(
            "metalgest.api.url",
            "http://localhost/Metalurgica-San-Jorge/clases/php/api.php");

    static {
        ensureCookieHandler();
    }

    private static synchronized void ensureCookieHandler() {
        CookieHandler handler = CookieHandler.getDefault();
        if (handler == null) {
            CookieManager cookies = new CookieManager();
            cookies.setCookiePolicy(CookiePolicy.ACCEPT_ALL);
            CookieHandler.setDefault(cookies);
        } else if (handler instanceof CookieManager) {
            ((CookieManager) handler).setCookiePolicy(CookiePolicy.ACCEPT_ALL);
        }
    }

    private static void clearCookies(String host) {
        CookieHandler handler = CookieHandler.getDefault();
        if (!(handler instanceof CookieManager)) return;
        CookieStore store = ((CookieManager) handler).getCookieStore();
        for (java.net.HttpCookie cookie : store.getCookies()) {
            String domain = cookie.getDomain();
            if (domain != null && (host.equalsIgnoreCase(domain) || host.endsWith("." + domain.replaceFirst("^\\.", "")))) {
                store.remove(null, cookie);
            }
        }
    }

    public static String get(String action) throws IOException {
        URL url = URI.create(API_BASE + "?action=" + encode(action)).toURL();
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("GET");
        conn.setConnectTimeout(4000);
        conn.setReadTimeout(4000);
        return readResponse(conn);
    }

    public static String post(String action, Map<String, String> data) throws IOException {
        URL url = URI.create(API_BASE + "?action=" + encode(action)).toURL();
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setDoOutput(true);
        conn.setConnectTimeout(4000);
        conn.setReadTimeout(4000);
        conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

        StringBuilder body = new StringBuilder();
        for (Map.Entry<String, String> entry : data.entrySet()) {
            if (body.length() > 0) {
                body.append('&');
            }
            body.append(encode(entry.getKey()));
            body.append('=');
            body.append(encode(entry.getValue() == null ? "" : entry.getValue()));
        }

        try (OutputStream os = conn.getOutputStream()) {
            os.write(body.toString().getBytes(StandardCharsets.UTF_8));
        }
        return readResponse(conn);
    }

    public static String postTo(String base, String action, Map<String, String> data) throws IOException {
        ensureCookieHandler();
        URL url = URI.create(base + "?action=" + encode(action)).toURL();
        if ("chat_logout".equals(action)) {
            clearCookies(url.getHost());
        }
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setDoOutput(true);
        conn.setConnectTimeout(4000);
        conn.setReadTimeout(4000);
        conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        StringBuilder body = new StringBuilder();
        for (Map.Entry<String, String> entry : data.entrySet()) {
            if (body.length() > 0) {
                body.append('&');
            }
            body.append(encode(entry.getKey())).append('=').append(encode(entry.getValue() == null ? "" : entry.getValue()));
        }
        try (OutputStream os = conn.getOutputStream()) {
            os.write(body.toString().getBytes(StandardCharsets.UTF_8));
        }
        String response = readResponse(conn);
        if ("chat_logout".equals(action)) {
            clearCookies(url.getHost());
        }
        return response;
    }

    public static String postMultipart(String action, Map<String, String> data, List<File> files) throws IOException {
        ensureCookieHandler();
        String boundary = "----MetalGest" + System.nanoTime();
        URL url = URI.create(API_BASE + "?action=" + encode(action)).toURL();
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setDoOutput(true);
        conn.setConnectTimeout(8000);
        conn.setReadTimeout(15000);
        conn.setRequestProperty("Content-Type", "multipart/form-data; boundary=" + boundary);
        try (DataOutputStream output = new DataOutputStream(conn.getOutputStream())) {
            for (Map.Entry<String, String> entry : data.entrySet()) {
                output.writeBytes("--" + boundary + "\r\n");
                output.writeBytes("Content-Disposition: form-data; name=\"" + entry.getKey() + "\"\r\n\r\n");
                output.write((entry.getValue() == null ? "" : entry.getValue()).getBytes(StandardCharsets.UTF_8));
                output.writeBytes("\r\n");
            }
            for (File file : files) {
                output.writeBytes("--" + boundary + "\r\n");
                output.writeBytes("Content-Disposition: form-data; name=\"adjuntos[]\"; filename=\"" + file.getName().replace("\"", "") + "\"\r\n");
                output.writeBytes("Content-Type: application/octet-stream\r\n\r\n");
                try (FileInputStream input = new FileInputStream(file)) {
                    byte[] buffer = new byte[8192];
                    int read;
                    while ((read = input.read(buffer)) != -1) output.write(buffer, 0, read);
                }
                output.writeBytes("\r\n");
            }
            output.writeBytes("--" + boundary + "--\r\n");
        }
        return readResponse(conn);
    }

    public static byte[] download(String action, Map<String, String> parameters) throws IOException {
        StringBuilder query = new StringBuilder();
        query.append("action=").append(encode(action));
        for (Map.Entry<String, String> entry : parameters.entrySet()) {
            query.append('&').append(encode(entry.getKey())).append('=').append(encode(entry.getValue()));
        }
        HttpURLConnection conn = (HttpURLConnection) URI.create(API_BASE + "?" + query).toURL().openConnection();
        conn.setConnectTimeout(8000);
        conn.setReadTimeout(15000);
        int status = conn.getResponseCode();
        InputStream input = status >= 200 && status < 400 ? conn.getInputStream() : conn.getErrorStream();
        if (input == null || status >= 400) throw new IOException("No se pudo descargar el archivo.");
        try (InputStream stream = input; ByteArrayOutputStream output = new ByteArrayOutputStream()) {
            byte[] buffer = new byte[8192];
            int read;
            while ((read = stream.read(buffer)) != -1) output.write(buffer, 0, read);
            return output.toByteArray();
        }
    }

    public static String getFrom(String base, String action) throws IOException {
        ensureCookieHandler();
        URL url = URI.create(base + "?action=" + encode(action)).toURL();
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("GET");
        conn.setConnectTimeout(4000);
        conn.setReadTimeout(4000);
        return readResponse(conn);
    }

    public static boolean isSuccess(String json) {
        return json != null && json.contains("\"success\":true");
    }

    private static String readResponse(HttpURLConnection conn) throws IOException {
        int status = conn.getResponseCode();
        InputStream is = status >= 200 && status < 400 ? conn.getInputStream() : conn.getErrorStream();
        if (is == null) {
            return "";
        }
        try (BufferedReader br = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8))) {
            StringBuilder sb = new StringBuilder();
            String line;
            while ((line = br.readLine()) != null) {
                sb.append(line);
            }
            String response = sb.toString();
            return response.startsWith("\uFEFF") ? response.substring(1) : response;
        }
    }

    private static String encode(String value) throws IOException {
        return URLEncoder.encode(value, StandardCharsets.UTF_8.name());
    }
}
