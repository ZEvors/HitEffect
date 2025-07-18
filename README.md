# HitEffect
HitEffect PVP MiniGames Server CubeOcean

### Fitur
- Support Libasynql, CoinAPI, FormAPI (Mendukung Libasynql, CoinAPI, FormAPI)
- Waterdog Support (Mendukung Waterdog)
- Auto Use After Switching Servers (Penggunaan Otomatis Setelah Berpindah Server)
- Easy Config Custom Price (Konfigurasi Mudah Harga Kustom)

### Database
```yaml
database:
  type: mysql
  mysql:
    host: 127.0.0.1
    username: root
    password: "password"
    schema: hit_effect
    port: 3306
  worker-limit: 2
```

### Permission
```yaml
  hiteffect.heart:
    default: op
  hiteffect.ink:
    default: op
  hiteffect.flame:
    default: op
  hiteffect.lava:
    default: op
  hiteffect.water:
    default: op
  hiteffect.smoke:
    default: op
```

### Effect Configuration
```yaml
effects:
  heart:
    price: 30000
    button: "Love Heart"
    permission: hiteffect.heart

  ink:
    price: 30000
    button: "Ink Splash"
    permission: hiteffect.ink

  flame:
    price: 30000
    button: "Fire Trail"
    permission: hiteffect.flame

  lava:
    price: 30000
    button: "Lava Spark"
    permission: hiteffect.lava

  water:
    price: 30000
    button: "Water Drop"
    permission: hiteffect.water

  smoke:
    price: 30000
    button: "Smoke Burst"
    permission: hiteffect.smoke
```
