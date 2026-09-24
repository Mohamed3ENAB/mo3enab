import { redirect } from 'next/navigation';
import { isLoggedIn } from '@/lib/auth';
import { LoginForm } from './form';

export const dynamic = 'force-dynamic';

export default async function LoginPage() {
  if (await isLoggedIn()) redirect('/admin');
  return (
    <main className="wrap page">
      <h1>لوحة الإدارة</h1>
      <p className="lede">الصفحة دي لمسؤول الخدمة بس.</p>
      <LoginForm />
    </main>
  );
}
