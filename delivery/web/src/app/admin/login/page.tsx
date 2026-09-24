import { redirect } from 'next/navigation';
import { isLoggedIn } from '@/lib/auth';
import { LoginForm } from './form';
import { Shield } from '@/components/icons';

export const dynamic = 'force-dynamic';

export default async function LoginPage() {
  if (await isLoggedIn()) redirect('/admin');
  return (
    <main className="wrap page" style={{ paddingTop: 60 }}>
      <div
        style={{
          width: 62, height: 62, borderRadius: 20, background: 'var(--hero)',
          color: '#fff', display: 'grid', placeItems: 'center', marginBottom: 18,
        }}
      >
        <Shield size={28} />
      </div>
      <h1>لوحة الإدارة</h1>
      <p className="lede">الصفحة دي لمسؤول الخدمة بس.</p>
      <div className="card">
        <LoginForm />
      </div>
    </main>
  );
}
