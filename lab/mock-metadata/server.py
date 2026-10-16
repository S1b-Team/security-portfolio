#!/usr/bin/env python3
"""Simulated cloud metadata endpoint for the isolated lab network only.
Returns clearly labeled FAKE temporary credentials. Not a real cloud service.
"""
from http.server import BaseHTTPRequestHandler, HTTPServer
import json

FAKE = {
    "Code": "Success",
    "Type": "FAKE-AWS-Lab-Credentials",
    "AccessKeyId": "FAKEACCESSKEYIDLABONLY",
    "SecretAccessKey": "FAKESECRETKEY_lab_only_not_real",
    "Token": "FAKESESSIONTOKEN_lab_only",
    "Expiration": "2099-01-01T00:00:00Z",
    "Warning": "FAKE credentials for defensive lab only — never valid outside this compose network",
}

class H(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        print("[mock-metadata]", fmt % args)

    def do_GET(self):
        path = self.path.split("?")[0]
        if path in ("/", "/latest/meta-data/", "/latest/meta-data"):
            body = b"iam\n"
        elif "security-credentials" in path or path.endswith("/lab-role"):
            body = json.dumps(FAKE, indent=2).encode()
        elif path.startswith("/latest/"):
            body = b"FAKE-lab-metadata\n"
        else:
            self.send_response(404)
            self.end_headers()
            return
        self.send_response(200)
        self.send_header("Content-Type", "application/json" if body.startswith(b"{") else "text/plain")
        self.send_header("X-Lab-Fake-Metadata", "true")
        self.end_headers()
        self.wfile.write(body)

if __name__ == "__main__":
    HTTPServer(("0.0.0.0", 80), H).serve_forever()
