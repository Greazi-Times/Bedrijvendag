# 🎓 Bedrijvendag – Student Internship Fair Website

This repository contains the source code for **Bedrijvendag**, a website built for organizing and promoting the student internship fair.  
The project is powered by **Laravel** (backend) and **Vue 3** (frontend) with **TailwindCSS** for styling and **Motion One (motion-vue)** for animations.

---

## ✨ Features

- 📚 Built with **Laravel 11** & **Vue 3**
- 🎨 Modern UI with **TailwindCSS**
- 🎬 Smooth animations powered by **Motion One**
- ⚡ Fast build system with **Vite**
- 🔄 Inertia.js integration for seamless SPA experience

---

## 📂 Important Project Structure

Only the most relevant folders you’ll usually edit:
```
/resources
├── /js          # Vue components and JavaScript logic
├── /views       # Blade templates
└── /css         # Tailwind & custom styles

/routes
├── web.php      # Main Laravel routes
└── api.php      # API routes

/public
└── /images      # Static assets (logos, hero backgrounds, etc.)
```

---

## 🚀 Getting Started

### 1. Clone the repository
```shell
git clone https://github.com/Greazi-Times/Bedrijvendag.git
cd Bedrijvendag
```

### 2. Install PHP dependencies
```shell
composer install
```

### 3. Install Node dependencies
```shell
npm install
```

### 4. Configure environment
```shell
Copy `.env.example` to `.env` and adjust database & app settings:
cp .env.example .env
php artisan key:generate
```

### 5. Run migrations
```shell
php artisan migrate
```

### 6. Start development servers
```shell
Run backend (Laravel) and frontend (Vite) in parallel:
php artisan serve
npm run dev
```

Now visit **http://localhost:8000** or **http://bedrijvendag.test** in your browser.

---

## 🛠 Development Scripts

- `npm run dev` – Start development with hot reload
- `npm run build` – Build assets for production
- `npm run format` – Format code with Prettier
- `npm run lint` – Lint and auto-fix code

---

## 🤝 Contributing

1. Fork the repository
2. Create a new feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m "Add new feature"`)
4. Push to your branch (`git push origin feature/your-feature`)
5. Open a Pull Request 🎉

---

## 📜 License

This project is open-source and available under the [MIT License](LICENSE).
