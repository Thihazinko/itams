"""
Capture full-viewport screenshots of the running Infra Ninja dev server
using Playwright + Microsoft Edge headless.

Prereqs (one-time):
    python -m pip install playwright

Run while dev server is up:
    python -m artisan serve --port=8765   (started elsewhere)
    python docs/capture_screenshots.py

Writes PNGs to docs/screenshots/.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright

BASE   = "http://127.0.0.1:8765"
ADMIN  = "thihazin@brycenmyanmar.com.mm"
PASSWORD = "CaptureTemp123!"  # temporary; the wrapper script sets+restores the real hash
RESET_TOKEN = "78c62992625b9a9f5f24cb910f5358a82ba3b695697b456b7ddaf50cea326659"

VIEWPORT = {"width": 1600, "height": 1000}
OUT = Path(__file__).resolve().parent / "screenshots"
OUT.mkdir(exist_ok=True)

# (filename, url, full_page?)  — full_page=False captures only the viewport
PUBLIC_PAGES = [
    ("01-login.png",            "/login",                                                   False),
    ("02-forgot-password.png",  "/forgot-password",                                         False),
    ("03-reset-password.png",   f"/reset-password/{RESET_TOKEN}?email={ADMIN}",             False),
]

PROTECTED_PAGES = [
    ("10-dashboard.png",                 "/dashboard",              True),
    ("11-pc-assets.png",                 "/pc-assets",              True),
    ("12-devices.png",                   "/devices",                True),
    ("13-subscriptions.png",             "/subscriptions",          True),
    ("14-licenses-contracts.png",        "/licenses-contracts",     True),
    ("15-notifications.png",             "/notifications",          True),
    ("16-profile.png",                   "/profile",                True),
    ("17-mail-settings.png",             "/mail-settings",          True),
    ("18-notification-settings.png",     "/notification-settings",  True),
    ("19-users.png",                     "/users",                  True),
]


def capture(page, url, out_path, full_page):
    print(f"  -> {url}  ({'full page' if full_page else 'viewport'})")
    page.goto(BASE + url, wait_until="networkidle", timeout=20_000)
    # Quiet down: hide the success/warning alert auto-fade so screenshots are stable
    page.wait_for_timeout(500)
    page.screenshot(path=str(out_path), full_page=full_page)


def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(channel="msedge", headless=True)
        context = browser.new_context(viewport=VIEWPORT)
        page = context.new_page()

        # ---- public pages first (no auth) ----
        print("Capturing public pages...")
        for name, url, full in PUBLIC_PAGES:
            capture(page, url, OUT / name, full)

        # ---- login via the form ----
        print("Logging in as admin...")
        page.goto(BASE + "/login", wait_until="networkidle")
        page.fill('input[name="email"]', ADMIN)
        page.fill('input[name="password"]', PASSWORD)
        page.click('button[type="submit"]')
        page.wait_for_url("**/dashboard", timeout=10_000)
        print("  -> logged in.")

        # ---- protected pages ----
        print("Capturing protected pages...")
        for name, url, full in PROTECTED_PAGES:
            capture(page, url, OUT / name, full)

        context.close()
        browser.close()
        print(f"Done. Files in {OUT}")


if __name__ == "__main__":
    main()
