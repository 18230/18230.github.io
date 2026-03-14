#!/usr/bin/env python3
import subprocess
import logging
from http.server import HTTPServer, BaseHTTPRequestHandler

PORT = 9090

ROUTES = {
    "/a": "/var/www/a",
    "/b": "/var/www/b",
}

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(message)s")

class Handler(BaseHTTPRequestHandler):

    def log_message(self, format, *args):
        pass

    def do_GET(self):
        self.do_POST()

    def do_POST(self):
        directory = ROUTES.get(self.path)
        if not directory:
            self.reply(404, "not found")
            return

        try:
            out = subprocess.check_output(
                ["git", "-C", directory, "pull"],
                stderr=subprocess.STDOUT,
                text=True
            )
            logging.info("[%s] %s", self.path, out.strip())
            self.reply(200, out)
        except subprocess.CalledProcessError as e:
            logging.error("[%s] %s", self.path, e.output.strip())
            self.reply(500, e.output)

    def reply(self, code, body):
        body = body.encode()
        self.send_response(code)
        self.send_header("Content-Length", len(body))
        self.end_headers()
        self.wfile.write(body)

HTTPServer(("127.0.0.1", PORT), Handler).serve_forever()


# 运行python3 webhook_server.py

# 使用方式如下：
# nginx配置对应的目录
# location = /project-name {
#    proxy_pass http://127.0.0.1:9090/project-name;
#    proxy_read_timeout 60s;
# }
