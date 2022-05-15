# backup-sftp-bundle

This repository is a read-only split from [ferienpass/ferienpass](https://github.com/ferienpass/ferienpass).

## Configuration

```
# .env.local

DB_STORAGE_HOST=example.org
DB_STORAGE_USERNAME=foo
DB_STORAGE_PASSWORD=abcdefg
```

```yaml
# .github/workflows/backup.yml

name: Backup

on:
  schedule:
    - cron:  '0 4 * * *' # Daily at 04:00

jobs:
  backup-db:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger backup task on remote
        uses: appleboy/ssh-action@master
        with:
          host: example.org
          username: foo
          key: "${{ secrets.SSH_PRIVATE_KEY }}"
          script: |
            cd /home/to/www/current
            php vendor/bin/contao-console contao:backup:create
```
