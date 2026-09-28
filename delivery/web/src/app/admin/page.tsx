import { redirect } from 'next/navigation';
import { isLoggedIn } from '@/lib/auth';
import {
  adminListProviders, adminListReports, adminListRequests, adminListPlaces,
  adminListApplications, adminStats, getZones,
} from '@/lib/queries';
import {
  SERVICE_LABELS, REPORT_REASONS, PLACE_LABELS,
  type ReportReason, type PlaceCategory,
} from '@/lib/types';
import { sinceArabic } from '@/lib/format';
import { AdminPanel } from './panel';

export const dynamic = 'force-dynamic';

export default async function AdminPage() {
  if (!(await isLoggedIn())) redirect('/admin/login');

  const [providers, reports, requests, places, applications, stats, zones] = await Promise.all([
    adminListProviders(),
    adminListReports(),
    adminListRequests(),
    adminListPlaces(),
    adminListApplications(),
    adminStats(),
    getZones(),
  ]);

  return (
    <AdminPanel
      zones={zones}
      providers={providers.map((p) => ({
        ...p,
        servicesLabel: p.services.map((s) => SERVICE_LABELS[s]).join('، '),
        since: p.availability_updated_at ? sinceArabic(p.availability_updated_at) : 'لسه مافتحش',
        openReports: Number(p.open_reports),
      }))}
      reports={reports.map((r) => ({
        ...r,
        reasonLabel: REPORT_REASONS[r.reason as ReportReason] ?? r.reason,
        since: sinceArabic(r.created_at),
      }))}
      requests={requests.map((r) => ({ ...r, since: sinceArabic(r.created_at) }))}
      places={places.map((p) => ({
        ...p,
        categoryLabel: PLACE_LABELS[p.category as PlaceCategory] ?? p.category,
      }))}
      applications={applications.map((a) => ({
        ...a,
        since: sinceArabic(a.created_at),
        servicesLabel: (a.services ?? [])
          .map((sv) => SERVICE_LABELS[sv as keyof typeof SERVICE_LABELS] ?? sv)
          .join('، '),
        categoryLabel: a.category
          ? (PLACE_LABELS[a.category as PlaceCategory] ?? a.category)
          : null,
      }))}
      stats={{
        drivers: Number(stats?.drivers ?? 0),
        live: Number(stats?.live ?? 0),
        places: Number(stats?.places ?? 0),
        pending: Number(stats?.pending ?? 0),
        openReports: Number(stats?.open_reports ?? 0),
        openRequests: Number(stats?.open_requests ?? 0),
      }}
    />
  );
}
