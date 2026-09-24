'use client';

import { useRef, useState } from 'react';
import { Camera } from './icons';

export function PhotoField({ label, hint }: { label: string; hint: string }) {
  const ref = useRef<HTMLInputElement>(null);
  const [preview, setPreview] = useState<string | null>(null);

  return (
    <div className="photofield">
      <span className="prev">
        {preview ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={preview} alt="" />
        ) : (
          <Camera size={24} />
        )}
      </span>
      <span className="t">
        <b>{label}</b>
        <span>{hint}</span>
      </span>
      <button type="button" className="pick" onClick={() => ref.current?.click()}>
        {preview ? 'غيّرها' : 'اختار صورة'}
      </button>
      <input
        ref={ref}
        type="file"
        name="photo"
        accept="image/jpeg,image/png,image/webp"
        onChange={(e) => {
          const f = e.target.files?.[0];
          setPreview(f ? URL.createObjectURL(f) : null);
        }}
      />
    </div>
  );
}
