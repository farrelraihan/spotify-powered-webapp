# Spotify Powered Webapp

A web application that allows you to save tracks and organize them using the Spotify API, built with Laravel and Blade.

## Features

- **Spotify Integration:** Authenticate with Spotify and access your music library.
- **Track Management:** Save your favorite tracks and organize them into custom playlists.
- **Playlist Organization:** Create, update, and manage playlists for better music organization.
- **Song Tagging:** Assign custom tags to tracks to categorize, filter, and quickly find your music.
- **Modern UI:** Clean and responsive interface powered by Blade templates.
- **User Authentication:** Secure login and user management via Laravel’s built-in auth system.

## Tech Stack

- **Backend:** [Laravel](https://laravel.com/) (PHP)
- **Frontend:** [Blade](https://laravel.com/docs/10.x/blade) templating engine
- **API:** [Spotify Web API](https://developer.spotify.com/documentation/web-api/)

## Getting Started

### Prerequisites

- PHP >= 8.0
- Composer
- Node.js & npm (for frontend assets)
- MySQL or another supported database

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/farrelraihan/spotify-powered-webapp.git
   cd spotify-powered-webapp
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install && npm run dev
   ```

3. **Set up environment variables:**
   - Copy `.env.example` to `.env`
     ```bash
     cp .env.example .env
     ```
   - Fill in your database credentials and your Spotify API credentials (`SPOTIFY_CLIENT_ID`, `SPOTIFY_CLIENT_SECRET`, `SPOTIFY_REDIRECT_URI`).

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

6. **Serve the application:**
   ```bash
   php artisan serve
   ```

7. **Access the app:**  
   Open [http://localhost:8000](http://localhost:8000) in your browser.

## Spotify API Setup

1. Register your application at [Spotify Developer Dashboard](https://developer.spotify.com/dashboard/applications).
2. Add your app’s callback/redirect URI in Spotify dashboard (should match `SPOTIFY_REDIRECT_URI` in `.env`).
3. Copy the Client ID and Client Secret to your `.env` file.

## Contribution

Contributions are welcome! Please open an issue or submit a pull request for improvements or bug fixes.

## License

This project is licensed under the [MIT License](LICENSE).

---

Built with ♥ by [farrelraihan](https://github.com/farrelraihan)
