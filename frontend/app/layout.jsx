import './globals.css';
import { ToastProvider } from '@/components/ToastContext';
import { ModalProvider } from '@/components/ModalContext';
import { CartProvider } from '@/components/CartContext';
import PWAInstall from '@/components/PWAInstall';

export const metadata = {
  title: 'Pedzio – Apoya a los emprendimientos de tu ciudad',
  description: 'Pide tus productos favoritos de emprendedores locales y recíbelos en la puerta de tu casa.',
  manifest: '/manifest.json',
  themeColor: '#F26A1B',
  appleWebApp: {
    capable: true,
    statusBarStyle: 'default',
    title: 'Pedzio',
  },
  icons: {
    icon: '/icons/icon-192.png',
    apple: '/icons/apple-touch-icon.png',
  },
};

export default function RootLayout({ children }) {
  return (
    <html lang="es" className="scroll-smooth">
      <body className="font-sans">
        <ToastProvider>
          <ModalProvider>
            <CartProvider>{children}</CartProvider>
          </ModalProvider>
        </ToastProvider>
        <PWAInstall />
      </body>
    </html>
  );
}
