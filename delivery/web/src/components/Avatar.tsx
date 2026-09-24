/** لون ثابت لكل اسم — نفس الشخص بياخد نفس اللون دايمًا. */
const PALETTE = [
  'linear-gradient(140deg,#00C07E,#00815A)',
  'linear-gradient(140deg,#5B9BFF,#2452D6)',
  'linear-gradient(140deg,#FFC24D,#F08C00)',
  'linear-gradient(140deg,#B07CFF,#6D2FD4)',
  'linear-gradient(140deg,#4FD1D9,#12879B)',
  'linear-gradient(140deg,#FF8A8A,#D63030)',
];

function hash(s: string) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
  return h;
}

export function Avatar({
  name,
  photoId,
  verified,
  size,
  children,
}: {
  name: string;
  photoId?: string | null;
  verified?: boolean;
  size?: number;
  children?: React.ReactNode;
}) {
  const initial = name.trim().charAt(0) || '؟';
  const style = size ? { width: size, height: size, fontSize: size * 0.38 } : undefined;

  // فيه صورة؟ اعرضها. مفيش؟ الحرف الأول على لون ثابت.
  if (photoId) {
    return (
      <div className="avatar photo" style={style}>
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={`/img/${photoId}`} alt="" loading="lazy" decoding="async" />
        {verified ? (
          <span className="vbadge" title="موثّق">
            {children}
          </span>
        ) : null}
      </div>
    );
  }

  return (
    <div
      className="avatar"
      style={{ ...style, background: PALETTE[hash(name) % PALETTE.length] }}
    >
      {initial}
      {verified ? (
        <span className="vbadge" title="موثّق">
          {children}
        </span>
      ) : null}
    </div>
  );
}
