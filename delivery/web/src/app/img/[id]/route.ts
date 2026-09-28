import { getImage } from '@/lib/images';

/** الصور محتواها ثابت والـ id عشوائي، فبنكاشها للأبد. */
export async function GET(_req: Request, ctx: { params: Promise<{ id: string }> }) {
  const { id } = await ctx.params;
  if (!/^[0-9a-f-]{36}$/i.test(id)) return new Response('Not found', { status: 404 });

  const img = await getImage(id);
  if (!img) return new Response('Not found', { status: 404 });

  return new Response(new Uint8Array(img.bytes), {
    headers: {
      'Content-Type': img.mime,
      'Content-Length': String(img.bytes.byteLength),
      'Cache-Control': 'public, max-age=31536000, immutable',
    },
  });
}
