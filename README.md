# Spotify Powered Webapp

A Laravel + Filament web application to save, tag, and organize your favorite songs and playlists using the Spotify API. Manage music, playlists, and custom tags with a beautiful admin dashboard built on [Filament](https://filamentphp.com/).

---

## Features

- **Spotify Integration:** Connect your Spotify account and import tracks directly from Spotify.
- **Track Management:** Save and organize your favorite tracks in your own library.
- **Playlist Organization:** Create and manage custom playlists.
- **Custom Song Tagging:** Assign custom tags (mood, activity, genre, etc.) to songs for powerful filtering and discovery.
- **Filament Admin Panel:** Modern, user-friendly admin dashboard for managing songs, tags, and playlists (CRUD).
- **Filtering & Search:** Quickly search and filter songs by tags, mood, activity, and more.
- **Secure Authentication:** Built-in user authentication with Laravel.

---

## Tech Stack

- **Backend:** [Laravel](https://laravel.com/) (PHP)
- **Admin Panel & Resource Management:** [Filament](https://filamentphp.com/)
- **Frontend:** [Blade](https://laravel.com/docs/10.x/blade) templating engine
- **API Integration:** [Spotify Web API](https://developer.spotify.com/documentation/web-api/)

---



## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js & npm (for frontend assets)
- MySQL or another supported database
- A Spotify Developer application (for API credentials)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/farrelraihan/spotify-powered-webapp.git
   cd spotify-powered-webapp/vibe-playlistsw
   ```

2. **Install backend dependencies:**
   ```bash
   composer install
   ```

3. **Install frontend dependencies:**
   ```bash
   npm install && npm run dev
   ```

4. **Set up your environment:**
   - Copy `.env.example` to `.env`:
     ```bash
     cp .env.example .env
     ```
   - Fill in your database credentials and your Spotify API details:
     - `SPOTIFY_CLIENT_ID`
     - `SPOTIFY_CLIENT_SECRET`
     - `SPOTIFY_REDIRECT_URI`

5. **Generate the application key:**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

7. **Serve the application:**
   ```bash
   php artisan serve
   ```

8. **Access the app:**
   Open [http://localhost:8000](http://localhost:8000) in your browser.

### Accessing the Filament Admin Panel

- The Filament admin dashboard is available at `/admin`.
- Log in using your application credentials.

---

## Spotify API Setup

1. Register your application at the [Spotify Developer Dashboard](https://developer.spotify.com/dashboard/applications).
2. Set the Redirect URI to match your `.env` (`SPOTIFY_REDIRECT_URI`).
3. Add your Client ID and Client Secret to your `.env` file.

---

## Database Models

- **Song:** Tracks imported from Spotify, with support for tags and audio features.
- **Playlist:** User-created playlists for organizing songs.
- **Tag:** Custom tags for categorizing songs by mood, activity, genre, etc.

---

## Custom Tagging System

- Tags can be created and managed via the Filament admin panel.
- Tag types supported: `mood`, `activity`, `genre` (extensible).
- Assign multiple tags to any song and filter/search easily.
- Tags auto-generate slugs for easy reference.

---

## Development

- Uses Laravel’s service container and Eloquent ORM.
- Admin/CRUD via Filament resources (see `app/Filament/Resources/`).
- Fully type-hinted and modern PHP codebase.

---

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for improvements or bug fixes.

---

## License

This project is licensed under the [MIT License](LICENSE).

---

Built with ♥ by [farrelraihan](https://github.com/farrelraihan)

---
