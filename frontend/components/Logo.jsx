import Link from 'next/link';

export default function Logo({ dark = false, href = '/', className = '' }) {
  return (
    <Link href={href} className={`logo ${dark ? 'text-white' : ''} ${className}`}>
      <div className="logo-icon">
        <svg viewBox="0 0 20 20" width="18" height="18">
          <path d="M10 2L3 7v11h14V7L10 2zm0 13a3 3 0 110-6 3 3 0 010 6z" fill="#fff" />
        </svg>
      </div>
      Pedzio
    </Link>
  );
}
