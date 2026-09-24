import { notFound } from 'next/navigation';
import { getThreadForProvider, getMessages } from '@/lib/queries';
import { Chat } from '@/components/Chat';
import { Chat as ChatIcon } from '@/components/icons';

export const dynamic = 'force-dynamic';

/** محادثة السائق على طلب معيّن — بتتفتح بلينك السائق السري. */
export default async function ProviderThread({
  params,
}: {
  params: Promise<{ ptoken: string; request: string }>;
}) {
  const { ptoken, request } = await params;
  const t = await getThreadForProvider(request, ptoken);
  if (!t) notFound();

  const messages = await getMessages(t.id);

  return (
    <main className="wrap page">
      <h1 style={{ display: 'flex', alignItems: 'center', gap: 9 }}>
        <ChatIcon size={22} /> محادثة على طلب
      </h1>
      <p className="lede">«{t.body}»</p>

      <Chat
        messages={messages}
        providerToken={ptoken}
        requestId={request}
        closed={Boolean(t.closed_at)}
      />
    </main>
  );
}
