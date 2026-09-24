import Link from 'next/link';

export function Foot() {
  const phone = process.env.NEXT_PUBLIC_SUPPORT_PHONE;
  return (
    <footer className="foot">
      <p>
        <Link href="/join">اشتغل معانا</Link> · <Link href="/admin">الإدارة</Link>
      </p>
      <p>
        <strong>في السكة</strong> خدمة مجتمعية مجانية بتعرض بيانات سواقين في المنطقة عشان تسهّل
        التواصل. إحنا مش طرف في أي اتفاق بينك وبين السائق، ومش بناخد عمولة، ومش بنحدد أسعار ملزمة،
        ومش مسؤولين عن جودة الخدمة أو أي خلاف.
      </p>
      <p>«موثّق» معناها إننا شوفنا بطاقة ورخصة السائق. مش ضمان.</p>
      {phone ? (
        <p>
          لأي شكوى أو طلب حذف بياناتك: <a href={`tel:${phone}`}>{phone}</a>
        </p>
      ) : null}
    </footer>
  );
}
