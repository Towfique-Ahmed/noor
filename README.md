# Noor

An Islamic resources website built with plain PHP — prayer times, Qur'an
reading, Qibla direction, Zakat and Fitrah calculators, Hadith collections and
more.

## Requirements

- PHP 8.1 or later
- The `curl` extension (the site falls back to `file_get_contents` if it is
  missing, but `curl` is recommended)
- No database and no Composer packages

## Running locally

```bash
php -S localhost:8000 -t public
```

Then open <http://localhost:8000>.

## License

MIT — see [LICENSE](LICENSE).
