#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import logging
import subprocess
from http.server import HTTPServer, BaseHTTPRequestHandler

PORT = 9090

# 路由表：路径 → 要 pull 的目录
ROUTES = {
    "/lajiao-pull": "/home/admin-shanhu",
    # 可继续添加更多路由，例如：
    # "/frontend-pull": "/home/web/project-frontend",
    # "/api-pull":     "/home/web/project-api",
}

# 日志配置（时间 + 级别 + 消息）
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S"
)

class GitPullHandler(BaseHTTPRequestHandler):

    def log_message(self, format, *args):
        """禁用默认的访问日志（太啰嗦），我们自己用 logging 记录"""
        return

    def do_GET(self):
        """GET 和 POST 都走同一套逻辑"""
        self.do_POST()

    def do_POST(self):
        directory = ROUTES.get(self.path)
        if not directory:
            self.reply(404, f"Route not found: {self.path}\n")
            return

        try:
            # 执行 git pull，捕获 stdout + stderr
            out = subprocess.check_output(
                ["git", "-C", directory, "pull"],
                stderr=subprocess.STDOUT,      # 合并 stderr 到 stdout
                text=True,                      # 返回 str 而不是 bytes
                timeout=30                      # 防止卡死（可选，根据需要调整）
            ).strip()

            logging.info("[%s] success → %s", self.path, out or "(already up to date)")
            self.reply(200, out or "Already up to date.\n")

        except subprocess.CalledProcessError as e:
            error_msg = e.output.strip() or "(no output)"
            logging.error("[%s] git pull failed → %s (return code: %d)",
                         self.path, error_msg, e.returncode)
            self.reply(500, f"Git pull failed (code {e.returncode}):\n{error_msg}\n")

        except subprocess.TimeoutExpired as e:
            logging.error("[%s] git pull timeout after %d seconds", self.path, e.timeout)
            self.reply(504, f"Git operation timed out after {e.timeout} seconds\n")

        except Exception as e:
            logging.exception("[%s] unexpected error", self.path)
            self.reply(500, f"Server internal error: {str(e)}\n")

    def reply(self, status_code: int, body: str):
        """统一回复方法"""
        body_bytes = body.encode("utf-8")
        self.send_response(status_code)
        self.send_header("Content-Type", "text/plain; charset=utf-8")
        self.send_header("Content-Length", str(len(body_bytes)))
        self.end_headers()
        self.wfile.write(body_bytes)


if __name__ == "__main__":
    server = HTTPServer(("127.0.0.1", PORT), GitPullHandler)
    logging.info("Git webhook server started on http://127.0.0.1:%d", PORT)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        logging.info("Server stopped by user")
        server.server_close()

# 运行方式：
# chmod +x git-pull-webhook.py
# nohup ./git-pull-webhook.py > webhook.log 2>&1 &


# nginx配置：
# location = /lajiao-pull {
#    proxy_pass http://127.0.0.1:9090/lajiao-pull;
#    proxy_read_timeout 60s;
# }
