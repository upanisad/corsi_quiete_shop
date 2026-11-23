#!/usr/bin/env python3
"""Genera i Blueprint del negozio La Quiete per WordPress Playground.

  python3 genera_blueprint.py                      # repo upanisad/corsi_quiete_shop, ramo main
  python3 genera_blueprint.py --repo UTENTE/REPO   # altro repo
  python3 genera_blueprint.py --prova              # solo il Blueprint di prova (dati dentro il file, senza immagini)

Uscite in out/:
  build-pulito.json / build-sporco.json   costruzione (si aprono una volta, poi si scarica lo ZIP)
  pulito.json / sporco.json               distribuzione ai corsisti (ripristinano lo ZIP)
  prova-<variante>.json + link            prova rapida con i dati incorporati
"""
import argparse, base64, json, pathlib, urllib.parse

QUI = pathlib.Path(__file__).resolve().parent
SCHEMA = "https://playground.wordpress.net/blueprint-schema.json"
WP = "/wordpress"
DATI = f"{WP}/wp-content/quiete-dati"
LANDING = "/wp-admin/edit.php?post_type=product"


def url(base, rel):
    return {"resource": "url", "url": base + rel}


def passi_costruzione(variante, base=None):
    """base=None -> dati incorporati nel Blueprint (prova); altrimenti URL raw su GitHub."""
    def dato(rel, locale):
        if base:
            return url(base, rel)
        return (QUI / locale).read_text(encoding="utf-8")

    passi = [
        {"step": "installPlugin", "pluginData": {"resource": "wordpress.org/plugins", "slug": "woocommerce"},
         "options": {"activate": True}},
        {"step": "installTheme", "themeData": {"resource": "wordpress.org/themes", "slug": "drinkify-lite"},
         "options": {"activate": True}},
        {"step": "setSiteLanguage", "language": "it_IT"},
        {"step": "mkdir", "path": f"{DATI}/img"},
        {"step": "mkdir", "path": f"{WP}/wp-content/mu-plugins"},
        {"step": "writeFile", "path": f"{DATI}/prodotti.json", "data": dato("dati/prodotti.json", "dati/prodotti.json")},
        {"step": "writeFile", "path": f"{DATI}/ordini.json", "data": dato("dati/ordini.json", "dati/ordini.json")},
        {"step": "writeFile", "path": f"{DATI}/build.php", "data": dato("src/build.php", "src/build.php")},
        {"step": "writeFile", "path": f"{WP}/wp-content/mu-plugins/quiete-regole.php",
         "data": dato("src/quiete-regole.php", "src/quiete-regole.php")},
    ]
    if base:  # le immagini solo con URL (nella prova inline non ci sono)
        for f in sorted((QUI / "immagini").glob("*.jpg")):
            passi.append({"step": "writeFile", "path": f"{DATI}/img/{f.name}", "data": url(base, f"immagini/{f.name}")})
    if base:
        passi.append({"step": "writeFile", "path": f"{DATI}/img/logo-quiete.png", "data": url(base, "marchio/logo-quiete.png")})
    passi.append({"step": "runPHP",
                  "code": f"<?php define('QUIETE_VARIANTE', '{variante}'); require '{DATI}/build.php';"})
    return passi


def blueprint(passi, titolo, descr, landing=LANDING):
    return {"$schema": SCHEMA,
            "meta": {"title": titolo, "description": descr, "author": "StyleSoft dML", "categories": ["didattica"]},
            "landingPage": landing, "login": True,
            "preferredVersions": {"php": "8.3", "wp": "latest"},
            "features": {"networking": True}, "steps": passi}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--repo", default="upanisad/corsi_quiete_shop")
    ap.add_argument("--branch", default="main")
    ap.add_argument("--prova", action="store_true")
    a = ap.parse_args()
    out = QUI / "out"
    out.mkdir(exist_ok=True)
    base = f"https://raw.githubusercontent.com/{a.repo}/{a.branch}/"

    if a.prova:
        for v in ("pulito", "sporco"):
            bp = blueprint(passi_costruzione(v, None), f"PROVA La Quiete ({v})", "Prova con dati incorporati")
            (out / f"prova-{v}.json").write_text(json.dumps(bp, ensure_ascii=False), encoding="utf-8")
            b64 = base64.b64encode(json.dumps(bp, ensure_ascii=False).encode("utf-8")).decode()
            (out / f"prova-{v}.url.txt").write_text("https://playground.wordpress.net/#" + b64)
            print(f"prova-{v}: {len(b64)//1024} KB in base64")
        return

    for v in ("pulito", "sporco"):
        etichetta = "negozio pulito" if v == "pulito" else "negozio con errori"
        bp = blueprint(passi_costruzione(v, base), f"COSTRUZIONE La Quiete ({etichetta})",
                       "Costruisce il negozio da zero. Da aprire una volta, poi scaricare lo ZIP.")
        (out / f"build-{v}.json").write_text(json.dumps(bp, ensure_ascii=False, indent=1), encoding="utf-8")
        distr = blueprint([
            {"step": "importWordPressFiles",
             "wordPressFilesZip": url(base, f"snapshot/quiete-{v}.zip")}],
            f"La Quiete nelle Cime: {etichetta}", "Negozio di prova per i corsi StyleSoft dML. Tutto inventato.")
        (out / f"{v}.json").write_text(json.dumps(distr, ensure_ascii=False, indent=1), encoding="utf-8")
        link = "https://playground.wordpress.net/?blueprint-url=" + urllib.parse.quote(base + f"{v}.json", safe="")
        print(f"{v}: {link}")
    print("Pubblica nel repo:", a.repo, "- cartelle: dati/ src/ immagini/ snapshot/ + build-*.json e pulito.json/sporco.json nella radice")


if __name__ == "__main__":
    main()
