import { notFound } from 'next/navigation';
import { getThreadByOwnerToken, getMessages } from '@/lib/queries';
import { Chat } from '@/components/Chat';
import { Alert, Chat as ChatIcon } from '@/components/icons';

export const dynamic = 'force-dynamic';

/** محادثة صاحب الطلب — بتتفتح بلينكه السري، من غير تسجيل. */
export default async function OwnerThread({ params }: { params: Promise<{ token: string }> }) {
  const { token } = await params;
  const t = await getThreadByOwnerToken(token);
  if (!t) notFound();

  const expired = new Date(t.expires_at).getTime() < Date.now();
  const messages = await getMessages(t.id);

  return (
    <main className="wrap page">
      <h1 style={{ display: 'flex', alignItems: 'center', gap: 9 }}>
        <ChatIcon size={22} /> محادثة طلبك
      </h1>
      <p className="lede">«{t.body}»</p>

      <Chat messages={messages} ownerToken={token} closed={Boolean(t.closed_at) || expired} />

      <div className="note warn" style={{ marginTop: 22 }}>
        <Alert className="ic" />
        <span>
          <strong>اللينك ده ليك انت بس.</strong> أي حد معاه يقدر يقرا المحادثة ويرد باسمك.
          والمحادثة بتقفل مع الطلب بعد ٦ ساعات.
        </span>
      </div>
    </main>
  );
}
