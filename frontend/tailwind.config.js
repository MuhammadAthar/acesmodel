/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        // Thread AI brand palette — inspired by dark fashion editorial aesthetic
        canvas:  '#0a0a0a',   // near-black background
        surface: '#111111',   // card surfaces
        border:  '#1e1e1e',   // subtle borders
        accent:  '#e8d5b7',   // warm champagne gold
        'accent-dim': '#c4b08a',
        'accent-glow': 'rgba(232,213,183,0.15)',
        muted:   '#666666',   // secondary text
        soft:    '#999999',   // tertiary text
      },
      fontFamily: {
        sans:    ['"Plus Jakarta Sans"', '"Plus Jakarta Sans Fallback"', 'system-ui', 'sans-serif'],
        display: ['"Plus Jakarta Sans"', '"Plus Jakarta Sans Fallback"', 'system-ui', 'sans-serif'],
      },
      backgroundImage: {
        'gradient-dark': 'linear-gradient(135deg, #0a0a0a 0%, #111111 100%)',
        'gradient-accent': 'linear-gradient(135deg, #e8d5b7 0%, #c4b08a 100%)',
        'gradient-radial': 'radial-gradient(ellipse at center, rgba(232,213,183,0.08) 0%, transparent 70%)',
      },
      animation: {
        'fade-up':    'fadeUp 0.5s ease forwards',
        'fade-in':    'fadeIn 0.4s ease forwards',
        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
        'shimmer':    'shimmer 1.5s ease-in-out infinite',
        'float':      'float 6s ease-in-out infinite',
      },
      keyframes: {
        fadeUp:    { from: { opacity: '0', transform: 'translateY(20px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        fadeIn:    { from: { opacity: '0' }, to: { opacity: '1' } },
        pulseSoft: { '0%,100%': { opacity: '1' }, '50%': { opacity: '0.5' } },
        shimmer:   { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
        float:     { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-8px)' } },
      },
    },
  },
  plugins: [],
}
