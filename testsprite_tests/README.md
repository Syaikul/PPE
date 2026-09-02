# TestSprite — Security & RBAC Tests

Folder ini menyimpan **laporan dan rencana tes** saja. File auto-generate (script Python, HTML, PDF, cache) diabaikan git — lihat `.gitignore`.

## Yang di-commit ke GitHub

| File | Isi |
|------|-----|
| `testsprite-mcp-test-report.md` | Laporan hasil tes keamanan & RBAC |
| `testsprite_backend_test_plan.json` | Rencana 20 kasus uji |
| `standard_prd.json` | PRD terstruktur untuk TestSprite |

## Akun uji

Jalankan seeder sebelum tes:

```bash
php artisan db:seed --class=TestUserSeeder
```

| Role | Email | Password |
|------|-------|----------|
| SuperAdmin | test@example.com | admin |
| AdminPPE | adminppe@example.com | password123 |
| HSE Officer | hse@example.com | password123 |
| Manager | manager@example.com | password123 |
| AdminPPE (gudang 2) | adminppe2@example.com | password123 |

## Ulangi tes (opsional)

Butuh TestSprite MCP + app jalan di `http://127.0.0.1:8001`. Script `.py` akan dibuat ulang otomatis di folder ini (tidak di-commit).
