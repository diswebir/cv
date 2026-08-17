<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <span class="badge"><span class="dot"></span> نسخه ۱.۰ — آماده استفاده</span>
      <h1 class="hero-title">کارت ویزیت مجازی بسازید؛<br>با یک اسکن، همیشه در دسترس باشید</h1>
      <p class="hero-sub">کارت ویزیت دیجیتال خود را با قالب‌های حرفه‌ای بسازید، کد QR اختصاصی با تم دلخواه بگیرید و با یک <b>لینک کوتاه</b> آن را با همه به اشتراک بگذارید. بدون نصب اپلیکیشن، کاملاً فارسی و موبایل‌فرندلی.</p>
      <div class="hero-actions">
        <a class="btn btn-lg btn-primary" href="<?= e(base_url('register')) ?>">شروع رایگان</a>
        <a class="btn btn-lg btn-ghost" href="<?= e(base_url('login')) ?>">ورود به حساب</a>
      </div>
      <?php if ((int)$stats['cards'] > 0 || (int)$stats['visits'] > 0): ?>
      <div class="hero-stats">
        <div class="hs-item"><strong><?= fa_num_format($stats['cards']) ?></strong><span>کارت ساخته شده</span></div>
        <div class="hs-item"><strong><?= fa_num_format($stats['users']) ?></strong><span>کاربر فعال</span></div>
        <div class="hs-item"><strong><?= fa_num_format($stats['visits']) ?></strong><span>بازدید ثبت شده</span></div>
      </div>
      <?php else: ?>
      <div class="hero-stats">
        <div class="hs-item"><strong>رایگان</strong><span>ثبت‌نام و استفاده رایگان بدون پرداخت هزینه</span></div>
        <div class="hs-item"><strong>۶ قالب</strong><span>حرفه‌ای و قابل‌سفارشی</span></div>
        <div class="hs-item"><strong>۱۸+ تم</strong><span>کد QR اختصاصی</span></div>
      </div>
      <?php endif; ?>
    </div>
    <div class="hero-visual">
      <div class="phone-mock">
        <div class="pm-screen">
          <div class="pm-cover"></div>
          <div class="pm-avatar">ر</div>
          <div class="pm-name">رامین احمدی</div>
          <div class="pm-role">طراح محصول</div>
          <div class="pm-btns">
            <span class="pm-btn p1">تماس</span>
            <span class="pm-btn p2">واتساپ</span>
            <span class="pm-btn p3">تلگرام</span>
          </div>
          <div class="pm-qr">
            <div class="pm-qr-grid"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
          </div>
        </div>
      </div>
      <div class="float-chip fc-1"><?= icon_svg('qr', 16); ?> اسکن کن!</div>
      <div class="float-chip fc-2"><?= icon_svg('link', 16); ?> <?= e(base_url('AbC123')) ?></div>
    </div>
  </div>
</section>

<section class="features">
  <div class="container">
    <div class="sec-head">
      <h2>هر چیزی که یک کارت ویزیت حرفه‌ای نیاز دارد</h2>
      <p>یک پلتفرم کامل، از ساخت کارت تا آمار بازدید</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card">
        <div class="fc-icon fc-1"><?= icon_svg('qr', 22); ?></div>
        <h3>کد QR با تم‌های متنوع</h3>
        <p>بیش از ۱۸ تم رنگی، گرادیانی، نقطه‌ای و گرد برای کد QR؛ با امکان افزودن لوگو در مرکز و دانلود تصویر.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-2"><?= icon_svg('link', 22); ?></div>
        <h3>لینک کوتاه اختصاصی</h3>
        <p>هر کارت یک لینک کوتاه منحصربه‌فرد می‌گیرد؛ مناسب چاپ روی سربرگ، بیلبورد و اسکن سریع.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-3"><?= icon_svg('map-pin', 22); ?></div>
        <h3>موقعیت مکانی و نقشه</h3>
        <p>آدرس و موقعیت روی نقشه را به کارت اضافه کنید تا مشتریان با یک لمس راه را پیدا کنند.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-4"><?= icon_svg('contact', 22); ?></div>
        <h3>ذخیره در مخاطبین</h3>
        <p>دکمه «افزودن به مخاطبین» با فرمت استاندارد vCard؛ سازگار با گوشی‌های اندروید و آیفون.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-5"><?= icon_svg('send', 22); ?></div>
        <h3>شبکه‌های اجتماعی</h3>
        <p>اینستاگرام، تلگرام، واتساپ، لینکدین و... — هر لینکی را با یک دکمه در کارت بگذارید.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-6"><?= icon_svg('dashboard', 22); ?></div>
        <h3>آمار بازدید</h3>
        <p>تعداد بازدید هر کارت، نمودار روزانه و جزئیات ورودی‌ها را ببینید.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-7"><?= icon_svg('card', 22); ?></div>
        <h3>۶ قالب حرفه‌ای</h3>
        <p>از کلاسیک و مینیمال تا نئونی؛ با رنگ‌بندی دلخواه برای هر کارت.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-8"><?= icon_svg('phone', 22); ?></div>
        <h3>کاملاً موبایل‌فرندلی</h3>
        <p>طراحی شده برای گوشی؛ چون ۹۹٪ اسکن‌ها روی موبایل انجام می‌شوند.</p>
      </div>
      <div class="feature-card">
        <div class="fc-icon fc-9"><?= icon_svg('shield', 22); ?></div>
        <h3>امن و قابل اطمینان</h3>
        <p>رمزنگاری رمز عبور، توکن ضد حملات CSRF، پرس‌وجوهای امن و کنترل کامل مدیر.</p>
      </div>
    </div>
  </div>
</section>

<section class="howto">
  <div class="container">
    <div class="sec-head">
      <h2>فقط در سه گام</h2>
      <p>از ثبت‌نام تا اسکن اول، کمتر از ۵ دقیقه</p>
    </div>
    <div class="steps-row">
      <div class="step-item">
        <div class="step-num">۱</div>
        <h3>حساب بسازید</h3>
        <p>در چند ثانیه ثبت‌نام کنید و وارد پنل کاربری شوید.</p>
      </div>
      <div class="step-item">
        <div class="step-num">۲</div>
        <h3>کارت را بسازید</h3>
        <p>اطلاعات، عکس، شبکه‌های اجتماعی و لوکیشن را اضافه کنید و قالب و رنگ را انتخاب کنید.</p>
      </div>
      <div class="step-item">
        <div class="step-num">۳</div>
        <h3>اسکن و اشتراک‌گذاری</h3>
        <p>کد QR را دانلود کنید یا لینک کوتاه را بفرستید؛ همه چیز آماده است.</p>
      </div>
    </div>
  </div>
</section>

<section class="templates-show">
  <div class="container">
    <div class="sec-head">
      <h2>قالب‌های رسمی کارت</h2>
      <p>شش قالب آماده با ظاهری حرفه‌ای، قابل شخصی‌سازی با دو رنگ دلخواه</p>
    </div>
    <div class="tpl-grid">
      <div class="tpl-cell">
        <div class="tpl-ph tpl-classic"><div class="tp-cover"></div><div class="tp-line l1"></div><div class="tp-line l2"></div></div>
        <span>کلاسیک</span>
      </div>
      <div class="tpl-cell">
        <div class="tpl-ph tpl-dark"><div class="tp-cover"></div><div class="tp-line l1"></div><div class="tp-line l2"></div></div>
        <span>تیره</span>
      </div>
      <div class="tpl-cell">
        <div class="tpl-ph tpl-minimal"><div class="tp-cover"></div><div class="tp-line l1"></div><div class="tp-line l2"></div></div>
        <span>مینیمال</span>
      </div>
      <div class="tpl-cell">
        <div class="tpl-ph tpl-gradient"><div class="tp-cover"></div><div class="tp-line l1"></div><div class="tp-line l2"></div></div>
        <span>گرادیانی</span>
      </div>
      <div class="tpl-cell">
        <div class="tpl-ph tpl-business"><div class="tp-cover"></div><div class="tp-line l1"></div><div class="tp-line l2"></div></div>
        <span>بیزینسی</span>
      </div>
      <div class="tpl-cell">
        <div class="tpl-ph tpl-neon"><div class="tp-cover"></div><div class="tp-line l1"></div><div class="tp-line l2"></div></div>
        <span>نئونی</span>
      </div>
    </div>
  </div>
</section>

<section class="qr-show">
  <div class="container qr-show-inner">
    <div class="qr-show-copy">
      <h2>کد QR را خودتان انتخاب کنید</h2>
      <p>رنگ، گرادیان، نقطه‌های گرد یا مربعی، لوگوی مرکز... هر تمی که می‌خواهید، انتخاب و دانلود کنید. مناسب چاپ با کیفیت بالا.</p>
      <a class="btn btn-lg btn-primary" href="<?= e(base_url('register')) ?>">همین حالا شروع کنید</a>
    </div>
    <div class="qr-show-gallery">
      <div class="mini-qr-item">
        <div class="mini-qr" data-theme="classic" data-dots="square"></div>
        <span class="qr-label">کلاسیک</span>
      </div>
      <div class="mini-qr-item">
        <div class="mini-qr" data-theme="dark" data-dots="round"></div>
        <span class="qr-label">تیره</span>
      </div>
      <div class="mini-qr-item">
        <div class="mini-qr" data-theme="gradient1" data-dots="circle"></div>
        <span class="qr-label">آبی-بنفش</span>
      </div>
      <div class="mini-qr-item">
        <div class="mini-qr" data-theme="gradient3" data-dots="square"></div>
        <span class="qr-label">صورتی-نارنجی</span>
      </div>
      <div class="mini-qr-item">
        <div class="mini-qr" data-theme="green" data-dots="round"></div>
        <span class="qr-label">سبز</span>
      </div>
      <div class="mini-qr-item">
        <div class="mini-qr" data-theme="purple" data-dots="circle"></div>
        <span class="qr-label">بنفش</span>
      </div>
    </div>
  </div>
</section>
