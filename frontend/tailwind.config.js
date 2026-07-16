/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/**/*.{js,jsx}',
    './components/**/*.{js,jsx}',
  ],
  theme: {
    extend: {
      colors: {
        orange: '#F26A1B',
        'orange-light': '#FFF3EB',
        dark: '#1A1A2E',
        'dark-nav': '#1E1E2F',
        gray: {
          DEFAULT: '#6B7280',
          light: '#F7F8FA',
          border: '#E5E7EB',
        },
        green: '#22C55E',
        yellow: '#F59E0B',
        blue: '#3B82F6',
        red: '#EF4444',
        purple: '#8B5CF6',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      borderRadius: {
        DEFAULT: '10px',
      },
      boxShadow: {
        card: '0 2px 12px rgba(0,0,0,0.08)',
        'card-lg': '0 8px 30px rgba(0,0,0,0.12)',
      },
      keyframes: {
        slideIn: {
          from: { transform: 'translateY(16px)', opacity: '0' },
          to: { transform: 'translateY(0)', opacity: '1' },
        },
      },
      animation: {
        slideIn: 'slideIn .25s ease',
      },
    },
  },
  plugins: [],
};
