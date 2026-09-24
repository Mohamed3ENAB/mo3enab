import { redirect } from 'next/navigation';
import { isLoggedIn } from '@/lib/auth';
import { adminListProviders, adminListReports, adminListRequests, getZones } from '@/lib/queries';
import { SERVICE_LABELS, REPORT_REASONS, type ReportReason } from '@/lib/types';
import { sinceArabic } from '@/lib/format';
import { AdminPanel } from './panel';

export const dynamic = 'force-dynamic';

export default async function AdminPage() {
  if (!(await isLoggedIn())) redirect('/admin/login');

  const [providers, reports, requests, zones] = await Promise.all([
    adminListProviders(),
    adminListReports(),
    adminListRequests(),
    getZones(),
  ]);

  return (
    <AdminPanel
      zones={zones}
      providers={providers.map((p) => ({
        ...p,
        servicesLabel: p.services.map((s) => SERVICE_LABELS[s]).join('، '),
        since: sinceArabic(p.availability_updated_at),
        openReports: Number(p.open_reports),
      }))}
      reports={reports.map((r) => ({
        ...r,
        reasonLabel: REPORT_REASONS[r.reason as ReportReason] ?? r.reason,
        since: sinceArabic(r.created_at),
      }))}
      requests={requests.map((r) => ({ ...r, since: sinceArabic(r.created_at) }))}
    />
  );
}
