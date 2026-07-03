"""Playwright: Screenshots vom LexCortex aufnehmen"""
from playwright.sync_api import sync_playwright
import os

BASE = "http://localhost/lexcortex"
OUT = "screenshots"
os.makedirs(OUT, exist_ok=True)

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page(viewport={"width": 1280, "height": 800})

    # 1. Dashboard
    print("1/5 Dashboard...")
    page.goto(f"{BASE}/index.php")
    page.wait_for_timeout(500)
    page.screenshot(path=f"{OUT}/01-dashboard.png", full_page=True)

    # 2. Neuer Fall Modal
    print("2/5 Fall anlegen...")
    page.goto(f"{BASE}/index.php")
    page.wait_for_timeout(300)
    page.click("text=+ Neuer Fall") if page.locator("text=+ Neuer Fall").count() else None
    page.wait_for_timeout(500)
    page.screenshot(path=f"{OUT}/02-neuer-fall.png", full_page=True)

    # 3. Case Detail (Timeline + Tasks)
    print("3/5 Case Detail...")
    page.goto(f"{BASE}/case_detail.php?id=1")
    page.wait_for_timeout(500)
    page.screenshot(path=f"{OUT}/03-timeline.png", full_page=True)

    # 4. Kalender
    print("4/5 Kalender...")
    page.goto(f"{BASE}/index.php?tab=calendar")
    page.wait_for_timeout(500)
    page.screenshot(path=f"{OUT}/04-kalender.png", full_page=True)

    # 5. Kalender Tagesübersicht (click first day with events)
    print("5/5 Tagesübersicht...")
    page.goto(f"{BASE}/index.php?tab=calendar")
    page.wait_for_timeout(500)
    # Klick auf einen Tag mit Events
    day = page.locator(".cal-day").filter(has=page.locator(".cal-event")).first
    if day.count():
        day.click()
        page.wait_for_timeout(500)
    page.screenshot(path=f"{OUT}/05-tagesansicht.png", full_page=True)

    browser.close()
    print(f"\n✅ {len(os.listdir(OUT))} Screenshots in '{OUT}/'")
