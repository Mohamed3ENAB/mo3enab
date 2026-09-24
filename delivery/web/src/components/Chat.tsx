'use client';

import { useState, useTransition } from 'react';
import { sendMessage } from '@/app/actions';
import type { ChatMessage } from '@/lib/queries';
import { sinceArabic } from '@/lib/format';
import { Send, Chat as ChatIcon } from './icons';

export function Chat({
  messages,
  ownerToken,
  providerToken,
  requestId,
  closed,
}: {
  messages: ChatMessage[];
  ownerToken?: string;
  providerToken?: string;
  requestId?: string;
  closed?: boolean;
}) {
  const mineIsOwner = Boolean(ownerToken);
  const [list, setList] = useState(messages);
  const [text, setText] = useState('');
  const [msg, setMsg] = useState<string | null>(null);
  const [pending, start] = useTransition();

  function submit(fd: FormData) {
    const body = String(fd.get('body') ?? '').trim();
    if (!body) return;
    start(async () => {
      const r = await sendMessage(fd);
      if (r.ok) {
        setList((l) => [
          ...l,
          {
            id: `tmp-${Date.now()}`,
            from_owner: mineIsOwner,
            sender_name: null,
            body,
            created_at: new Date().toISOString(),
          },
        ]);
        setText('');
        setMsg(null);
      } else {
        setMsg(r.message ?? 'مقدرناش نبعت الرسالة.');
      }
    });
  }

  return (
    <>
      {list.length === 0 ? (
        <p className="chat-empty">
          <ChatIcon size={26} />
          <br />
          لسه مفيش رسايل. ابدأ الكلام.
        </p>
      ) : (
        <div className="chat">
          {list.map((m) => {
            const mine = m.from_owner === mineIsOwner;
            return (
              <div className={`bubble-msg ${mine ? 'mine' : 'theirs'}`} key={m.id}>
                {!mine ? (
                  <span className="who">{m.from_owner ? 'صاحب الطلب' : (m.sender_name ?? 'سائق')}</span>
                ) : null}
                {m.body}
                <span className="at">{sinceArabic(m.created_at)}</span>
              </div>
            );
          })}
        </div>
      )}

      {msg ? <p className="msg bad">{msg}</p> : null}

      {closed ? (
        <p className="lede">المحادثة اتقفلت.</p>
      ) : (
        <form action={submit} style={{ display: 'flex', gap: 9, alignItems: 'flex-end' }}>
          {ownerToken ? <input type="hidden" name="owner_token" value={ownerToken} /> : null}
          {providerToken ? <input type="hidden" name="provider_token" value={providerToken} /> : null}
          {requestId ? <input type="hidden" name="request_id" value={requestId} /> : null}
          <label className="field" style={{ flex: 1, margin: 0 }}>
            <textarea
              name="body"
              value={text}
              onChange={(e) => setText(e.target.value)}
              maxLength={1000}
              rows={2}
              placeholder="اكتب رسالتك"
              style={{ minHeight: 56 }}
              aria-label="رسالتك"
            />
          </label>
          <button className="btn call" type="submit" disabled={pending} aria-label="ابعت">
            <Send />
          </button>
        </form>
      )}
    </>
  );
}
