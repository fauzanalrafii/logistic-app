/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue", // <--- PENTING: Supaya file Vue terbaca
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}