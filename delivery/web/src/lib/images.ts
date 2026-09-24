import 'server-only';
import sharp from 'sharp';
import { q1 } from './db';

export const MAX_UPLOAD = 6 * 1024 * 1024; // ٦ ميجا قبل التصغير
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

export type ImageResult = { ok: true; id: string } | { ok: false; message: string };

/**
 * بتصغّر الصورة لـ 256×256 webp قبل ما تتخزن.
 * صورة الموبايل 4 ميجا بتبقى ~15 كيلو، فـ 200 صورة = 3 ميجا في
 * قاعدة البيانات. مفيش خدمة تخزين زيادة تظبطها وتدفع فيها.
 */
export async function storeImage(file: File | null): Promise<ImageResult | null> {
  if (!file || file.size === 0) return null;

  if (file.size > MAX_UPLOAD)
    return { ok: false, message: 'الصورة كبيرة أوي. أقصى حجم ٦ ميجا.' };
  if (!ACCEPTED.includes(file.type))
    return { ok: false, message: 'الصورة لازم تكون JPG أو PNG أو WEBP.' };

  let out: Buffer;
  try {
    out = await sharp(Buffer.from(await file.arrayBuffer()))
      .rotate() // يظبط اتجاه صور الموبايل
      .resize(256, 256, { fit: 'cover', position: 'attention' })
      .webp({ quality: 80 })
      .toBuffer();
  } catch {
    return { ok: false, message: 'مقدرناش نقرا الصورة. جرّب صورة تانية.' };
  }

  const row = await q1<{ id: string }>(
    `insert into images (mime, bytes, byte_size) values ('image/webp', $1, $2) returning id`,
    [out, out.byteLength]
  );
  return row ? { ok: true, id: row.id } : { ok: false, message: 'مقدرناش نحفظ الصورة.' };
}

export async function getImage(id: string) {
  return q1<{ mime: string; bytes: Buffer }>(
    `select mime, bytes from images where id = $1`,
    [id]
  );
}
