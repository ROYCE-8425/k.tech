"""
VPS Deployment script — Smart CV Matcher
Uses paramiko to SSH/SFTP without needing sshpass or plink.
"""
import os
import sys
import stat
import time
import paramiko

# ── Config ────────────────────────────────────────────────────────────────────
HOST     = "160.191.237.64"
PORT     = 22
USER     = "root"
PASSWORD = "t8liBkhqVHMX0HhP"
REMOTE   = "/var/www/smartcv"
LOCAL    = r"D:\web\cpanel_public_html"

# Directories to upload (relative to LOCAL)
UPLOAD_DIRS = ["backend", "ai-service", "deploy", "frontend", "docs", "docker"]
UPLOAD_FILES = ["docker-compose.yml"]

# Patterns to skip during upload
SKIP_DIRS = {
    "vendor", "node_modules", ".git", "__pycache__", ".venv",
    "dist", "build", ".next", ".cache", "storage",
}
SKIP_EXTS = {".pyc", ".pyo", ".log", ".sqlite", ".db"}

# ── Helpers ───────────────────────────────────────────────────────────────────
def log(msg): print(f"  ✓  {msg}", flush=True)
def step(msg): print(f"\n━━━ {msg} ━━━", flush=True)
def warn(msg): print(f"  !  {msg}", flush=True)

def remote_mkdir(sftp, path):
    """Create remote directory tree, ignore if exists."""
    parts = path.replace("\\", "/").split("/")
    current = ""
    for part in parts:
        if not part:
            continue
        current += "/" + part
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)

def should_skip(name, is_dir):
    if is_dir and name in SKIP_DIRS:
        return True
    if not is_dir:
        _, ext = os.path.splitext(name)
        if ext in SKIP_EXTS:
            return True
        if name.startswith(".env") and name != ".env.example":
            return True
    return False

def upload_dir(sftp, local_path, remote_path, depth=0):
    """Recursively upload a directory via SFTP."""
    remote_mkdir(sftp, remote_path)
    count = 0
    try:
        entries = os.listdir(local_path)
    except PermissionError:
        warn(f"Permission denied: {local_path}")
        return 0
    for name in entries:
        lp = os.path.join(local_path, name)
        rp = remote_path + "/" + name
        is_dir = os.path.isdir(lp)
        if should_skip(name, is_dir):
            continue
        if is_dir:
            count += upload_dir(sftp, lp, rp, depth + 1)
        else:
            try:
                sftp.put(lp, rp)
                count += 1
                if count % 50 == 0:
                    print(f"    ... {count} files uploaded", flush=True)
            except Exception as e:
                warn(f"Skip {rp}: {e}")
    return count

def stream_command(channel, timeout=600):
    """Stream stdout/stderr from an SSH channel until done or timeout."""
    channel.settimeout(5)
    start = time.time()
    while True:
        if channel.exit_status_ready():
            # Drain remaining output
            while channel.recv_ready():
                chunk = channel.recv(4096).decode("utf-8", errors="replace")
                print(chunk, end="", flush=True)
            while channel.recv_stderr_ready():
                chunk = channel.recv_stderr(4096).decode("utf-8", errors="replace")
                print(chunk, end="", flush=True)
            break
        if channel.recv_ready():
            chunk = channel.recv(4096).decode("utf-8", errors="replace")
            print(chunk, end="", flush=True)
        if channel.recv_stderr_ready():
            chunk = channel.recv_stderr(4096).decode("utf-8", errors="replace")
            print(chunk, end="", flush=True)
        if time.time() - start > timeout:
            warn("Timeout reached")
            break
        time.sleep(0.2)
    return channel.recv_exit_status()

def run_ssh(ssh, cmd, timeout=30, quiet=False):
    """Run a single command, return (stdout, rc)."""
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", errors="replace").strip()
    err = stderr.read().decode("utf-8", errors="replace").strip()
    rc  = stdout.channel.recv_exit_status()
    if not quiet and out:
        print(out, flush=True)
    if err and rc != 0 and not quiet:
        print(f"  STDERR: {err}", flush=True)
    return out, rc

# ── Main ─────────────────────────────────────────────────────────────────────
def main():
    step("Connecting to VPS")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=20)
    log(f"Connected to {HOST}")

    # ── Step 1: Create remote dir ────────────────────────────────────────────
    step("Creating remote directory")
    run_ssh(ssh, f"mkdir -p {REMOTE}")
    log(f"Directory {REMOTE} ready")

    # ── Step 2: Upload files ─────────────────────────────────────────────────
    step("Uploading project files (skipping vendor/, node_modules/, .git/)")
    sftp = ssh.open_sftp()

    total = 0
    for d in UPLOAD_DIRS:
        local_d  = os.path.join(LOCAL, d)
        remote_d = REMOTE + "/" + d
        if not os.path.exists(local_d):
            warn(f"Skipping missing dir: {d}")
            continue
        print(f"  Uploading {d}/...", flush=True)
        n = upload_dir(sftp, local_d, remote_d)
        total += n
        log(f"{d}/ — {n} files")

    for f in UPLOAD_FILES:
        local_f  = os.path.join(LOCAL, f)
        remote_f = REMOTE + "/" + f
        if os.path.exists(local_f):
            sftp.put(local_f, remote_f)
            total += 1
            log(f"{f}")
        else:
            warn(f"Missing: {f}")

    sftp.close()
    log(f"Total uploaded: {total} files")

    # ── Step 3: Run setup-vps.sh ─────────────────────────────────────────────
    step("Running setup-vps.sh (this takes 5-10 minutes...)")
    transport = ssh.get_transport()
    channel   = transport.open_session()
    channel.set_combine_stderr(False)
    channel.exec_command(f"cd {REMOTE} && bash deploy/setup-vps.sh 2>&1")
    rc = stream_command(channel, timeout=900)  # 15 min max
    if rc == 0:
        log("setup-vps.sh completed successfully")
    else:
        warn(f"setup-vps.sh exited with code {rc}")

    # ── Step 4: Health check ─────────────────────────────────────────────────
    step("Health checks")
    out, _ = run_ssh(ssh, "curl -s -o /dev/null -w '%{http_code}' http://localhost/api/up 2>/dev/null || echo 'not_ready'")
    log(f"Laravel /api/up: {out}")

    out, _ = run_ssh(ssh, "curl -s -o /dev/null -w '%{http_code}' http://localhost:8001/docs 2>/dev/null || echo 'not_ready'")
    log(f"AI service /docs: {out}")

    out, _ = run_ssh(ssh, "systemctl is-active smartcv-ai 2>/dev/null || echo 'not found'")
    log(f"AI service systemd: {out}")

    ssh.close()
    step("Deployment complete")
    print(f"\n  App URL:     http://{HOST}/demo")
    print(f"  API URL:     http://{HOST}/api/ml/ai-match")
    print(f"  AI Docs:     http://{HOST}:8001/docs\n")

if __name__ == "__main__":
    main()
