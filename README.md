# Vesho CRM WordPress Plugin

Privaatne CRM süsteem Vesho OÜ jaoks — klientide, hoolduste, töökäskude, inventuuri ja e-poe haldus.

## Versioon
Praegune versioon: vaata `vesho-crm.php` failis `VESHO_CRM_VERSION` konstant.

## Arendus

### Lokaalne arendus
Plugin asub: `wp-content/plugins/vesho-crm/`
Teema asub: `wp-content/themes/vesho/`

### Uue versiooni väljalase

**Variant A — GitHub tag (automaatne):**
```bash
git add .
git commit -m "v1.6.6: kirjeldus"
git tag v1.6.6
git push origin main --tags
```
→ GitHub Actions loob automaatselt ZIP + Release

**Variant B — WordPress admin (käsitsi):**
1. Admin → Vesho CRM → 🚀 Uuendused
2. Sisesta uus versiooninumber
3. Lisa muudatuste logi
4. Klõpsa "Loo uuenduspakett"
→ ZIP + JSON luuakse serveris, live server näeb uuendust

## Live serveri seadistus (esimene kord)

1. Laadi alla ZIPid siit → GitHub Releases
2. Hostin: cPanel → File Manager → `wp-content/plugins/` → Upload `vesho-crm.zip` → Extract
3. Hostin: cPanel → File Manager → `wp-content/themes/` → Upload `vesho-theme.zip` → Extract
4. WordPress Admin → Pluginad → Aktiveeri "Vesho CRM"
5. WordPress Admin → Välimus → Teemad → Aktiveeri "Vesho"

## FTP automaatdeploy seadistus

GitHub repo → Settings → Secrets → Actions:
- `FTP_HOST` = ftp.vesho.ee
- `FTP_USER` = ftp kasutajanimi
- `FTP_PASS` = ftp parool

Peale seda iga `git push` deployib automaatselt live serverisse.

## Struktuur

```
vesho-crm/
├── vesho-crm.php              # Plugin peafail + AJAX handlers
├── includes/
│   ├── class-admin.php        # Admin panel
│   ├── class-client-portal.php # Kliendi portaal
│   ├── class-worker-portal.php # Töötaja portaal
│   ├── class-database.php     # DB skeema
│   ├── class-api.php          # REST API
│   ├── class-updater.php      # Update mehhanism
│   └── shortcodes.php         # Lühikoodid
└── admin/
    └── views/                 # Admin vaated
```
