<?php
$indexContent = file_get_contents('/mnt/webdata/htdocs/index.html');

// Extract header
$headerPattern = '/^(.*?)<main class="container main-wrapper">/s';
preg_match($headerPattern, $indexContent, $matchesHeader);
$header = $matchesHeader[1];

// Extract footer
$footerPattern = '/<\/main>\s*<!-- Footer -->(.*?)$/s';
preg_match($footerPattern, $indexContent, $matchesFooter);
$footer = "    </main>\n\n    <!-- Footer -->" . $matchesFooter[1];

// Update Title and Menu
$header = str_replace('<title>Beranda — BP3NU Raden Rahmat Balongbendo</title>', '<title>Berita & Artikel — BP3NU Raden Rahmat</title>', $header);
$header = str_replace('<li><a href="#" class="active">Beranda</a></li>', '<li><a href="index.html">Beranda</a></li>', $header);
$header = str_replace('<li><a href="#">Berita & Artikel</a></li>', '<li><a href="berita.html" class="active">Berita & Artikel</a></li>', $header);

// Remove the Hero section from Berita so it doesn't look like the homepage
$header = preg_replace('/<!-- Hero -->.*?<\/section>/s', '', $header);

// Data structure for the news
$newsData = [
    [
        'title' => 'Bulan suci Ramadhan telah tiba, saatnya menata ulang ritme hidup dengan lebih banyak makna',
        'date' => '19 Feb 2026',
        'author' => 'smartba.official',
        'img' => 'api/ig_cache/DU7JCVUk-Xo.jpg',
        'content' => '<p>Bulan suci Ramadhan telah tiba, menebarkan haruman keberkahan dan kedamaian di setiap sudut kehidupan kita. Momentum istimewa ini bukan sekadar rutinitas tahunan yang datang dan pergi, melainkan sebuah panggilan spiritual untuk menata ulang ritme hidup kita dengan lebih banyak makna, introspeksi diri, dan memperkuat ikatan keimanan kita kepada Sang Pencipta.</p><p>Di SMA Raden Rahmat, penyambutan bulan Ramadhan selalu dipenuhi dengan antusiasme yang luar biasa dari seluruh elemen sekolah. Para siswa, guru, dan staf bersama-sama menciptakan lingkungan yang mendukung terwujudnya ibadah yang khusyuk. Ramadhan tahun ini, sekolah mengadakan berbagai program khusus, mulai dari kajian kitab salaf, pesantren kilat, hingga bakti sosial pembagian takjil di sekitar lingkungan Balongbendo.</p><p>Kegiatan-kegiatan ini dirancang sedemikian rupa agar tidak hanya mentransfer pengetahuan keagamaan, tetapi juga menumbuhkan empati, kepedulian sosial, dan kemandirian siswa. Melalui pesantren kilat, siswa diajak untuk menyelami makna hakiki dari puasa, bukan sekadar menahan lapar dan dahaga, tetapi juga melatih pengendalian diri dari amarah, prasangka buruk, dan segala hal yang dapat mengurangi pahala puasa. Pembiasaan-pembiasaan baik seperti shalat dhuha berjamaah, tilawah Al-Quran, dan tarawih bersama menjadi rutinitas harian yang menyejukkan hati.</p><p>Lebih jauh lagi, bulan Ramadhan adalah kesempatan berharga untuk memperbaiki hubungan antar sesama manusia (hablum minannas). Dalam suasana yang penuh toleransi dan saling memaafkan, setiap individu didorong untuk melebur ego dan mempererat tali silaturahmi. Berbagi takjil kepada masyarakat yang melintas di depan sekolah bukan hanya sekadar memberi makanan, melainkan membagikan kebahagiaan dan kepedulian. Dari kegiatan sederhana ini, para siswa belajar tentang pentingnya berbagi dan merasakan kebahagiaan dalam memberi.</p><p>Sebagai institusi pendidikan yang berlandaskan nilai-nilai Ahlussunnah wal Jamaah, BP3NU Raden Rahmat berharap bahwa gemblengan selama bulan suci ini akan melahirkan generasi muda yang tidak hanya unggul dalam akademik, tetapi juga memiliki akhlakul karimah yang kokoh. Generasi yang siap menghadapi tantangan zaman dengan keteguhan iman, kepekaan sosial, dan dedikasi untuk selalu menebar manfaat bagi sekitarnya. Semoga Ramadhan tahun ini membawa transformasi positif dalam diri kita semua, menjadikan kita insan yang lebih baik, lebih sabar, dan lebih bersyukur atas segala nikmat-Nya.</p>'
    ],
    [
        'title' => '🌙 Marhaban Ya Ramadhan 1447 H 🌙',
        'date' => '18 Feb 2026',
        'author' => 'smartba.official',
        'img' => 'api/ig_cache/DU4-TIuk9_U.jpg',
        'content' => '<p>Marhaban Ya Ramadhan 1447 H! Bulan suci yang penuh ampunan dan keberkahan kembali menyapa kita. Saatnya kembali pada-Nya, membersihkan hati dari segala prasangka, menguatkan iman dengan ibadah, serta menjadikan kesempatan ini sebagai titik tolak perbaikan diri menuju insan yang lebih paripurna.</p><p>Keluarga besar Raden Rahmat menyambut kedatangan bulan mulia ini dengan penuh rasa syukur dan suka cita. Segenap civitas akademika bersiap mengisi hari-hari di bulan puasa dengan berbagai amal kebajikan yang produktif. Sebagaimana pesan yang selalu disampaikan oleh para ulama dan kiai pendiri yayasan, Ramadhan adalah madrasah ruhaniyah, sebuah sekolah spiritual di mana kita dilatih untuk tunduk pada kehendak Allah SWT secara total dan menyucikan hati dari penyakit-penyakit batin.</p><p>Dalam ranah pendidikan, Ramadhan dimaknai sebagai bulan tarbiyah (pendidikan). Para pendidik di lingkungan Raden Rahmat tidak hanya berfokus pada penyelesaian kurikulum akademik, tetapi lebih menekankan pada pendidikan karakter beralaskan nilai-nilai puasa. Siswa-siswi dibimbing untuk memahami esensi kejujuran—karena puasa adalah ibadah yang sangat privat antara hamba dan Tuhannya. Mereka diajarkan empati melalui rasa lapar, sehingga muncul kesadaran untuk senantiasa membantu mereka yang kurang beruntung.</p><p>Selain itu, tradisi-tradisi baik seperti membangunkan sahur, tadarus Al-Quran selepas shalat tarawih, dan i\'tikaf di sepuluh malam terakhir menjadi agenda yang sangat direkomendasikan dan difasilitasi oleh pihak sekolah. Masjid sekolah dihidupkan dengan berbagai kajian keislaman yang dipandu secara langsung oleh para dewan guru yang kompeten. Tidak hanya itu, lomba-lomba Islami seperti Musabaqah Tilawatil Quran, kaligrafi, dan pidato bahasa Arab (Muhadharah) juga digelar untuk memupuk bakat dan kreativitas siswa di bidang keagamaan.</p><p>Mari kita jadikan Ramadhan 1447 H ini sebagai lembaran baru untuk mengukir amal saleh. Mari bersihkan niat, perbanyak doa, dan terus berbuat baik satu sama lain. Semoga kita semua diberikan kesehatan, kekuatan, dan kelapangan dalam menjalankan seluruh rangkaian ibadah di bulan ini, serta meraih predikat ketakwaan (muttaqin) sebagaimana yang dijanjikan oleh Allah SWT.</p>'
    ],
    [
        'title' => 'Ramadhan Sudah Didepan Mata, Mari Kita Sambut Bulan Yang Mulia Dengan Suka Cita',
        'date' => '18 Feb 2026',
        'author' => 'miradenrahmatbalongbendo',
        'img' => 'api/ig_cache/DU4UnnFgUfJ.jpg',
        'content' => '<p>Ramadhan Sudah Didepan Mata, Mari Kita Sambut Bulan Yang Mulia Dengan Suka Cita. Kata "Ramadhan" yang berasal dari bahasa Arab "ramada", bermakna membakar atau panas yang sangat terik. Secara filosofis, bulan ini hadir untuk membakar segala dosa-dosa masa lalu, menyucikan jiwa kita agar kembali fitrah dan bersih tanpa noda. Menyambut kedatangan bulan istimewa ini membutuhkan persiapan yang tidak sekadar fisik, tetapi juga persiapan mental dan kelimuan agar ibadah yang kita lakukan di dalamnya menjadi optimal dan berkualitas.</p><p>Di tingkat Madrasah Ibtidaiyah (MI) Raden Rahmat, pengenalan terhadap bulan suci Ramadhan dikemas dengan pendekatan yang hangat, menyenangkan, dan ramah anak. Melalui pendekatan edutainment, guru-guru menyelipkan nilai-nilai puasa, tata cara shalat tarawih, hingga keutamaan bersedekah di bulan puasa melalui kisah-kisah teladan Nabi dan para sahabat. Tujuannya adalah untuk menanamkan kecintaan mendalam terhadap bulan suci ini sejak usia sedini mungkin, agar kelak ibadah puasa bukan lagi menjadi sebuah kewajiban yang memberatkan, tetapi menjadi kebutuhan dan kerinduan spiritual.</p><p>Salah satu tradisi yang tak pernah lekang oleh waktu di MI Raden Rahmat adalah kegiatan pawai Tarhib Ramadhan. Para siswa beserta guru dan walimurid bersama-sama menyusuri jalanan desa sembari menggemakan selawat, menggunakan berbagai atribut warna-warni ciptaan mereka sendiri. Pawai ini tidak hanya menjadi syiar keislaman yang menggembirakan, tetapi juga medium untuk mengeratkan kebersamaan antara sekolah dan masyarakat sekitar, sekaligus mengingatkan masyarakat bahwa tamu agung bernana Ramadhan sebentar lagi akan mengetuk pintu kehidupan kita.</p><p>Oleh karena itu, persiapan fisik melalui menjaga kesehatan, mengonsumsi makanan bergizi, serta persiapan ilmu dengan mengulang kembali kajian tentang fiqih puasa perlu ditingkatkan. Seluruh rangkaian kegiatan dirancang secara komprehensif untuk memastikan setiap anak merasa dilibatkan dalam kegembiraan menyambut Ramadhan. Semua diupayakan untuk membentuk karakter anak-anak menjadi pribadi pembelajar yang saleh, optimis, dan siap memberikan yang terbaik untuk agama, nusa, dan bangsa dalam balutan semangat Islami.</p>'
    ],
    [
        'title' => 'PROFIL MADRASAH IBTIDAIYAH RADEN RAHMAT BALONGBENDO',
        'date' => '17 Feb 2026',
        'author' => 'miradenrahmatbalongbendo',
        'img' => 'api/ig_cache/DU3yA_hgNoU.jpg',
        'content' => '<p>Madrasah Ibtidaiyah (MI) Raden Rahmat Balongbendo telah berdiri sebagai salah satu pilar pendidikan dasar Islam yang kokoh di kawasan Balongbendo, Sidoarjo. Sejak awal pendiriannya, lembaga ini mengemban amanah besar untuk menyelenggarakan pendidikan dasar yang tidak sekadar mencetak generasi cerdas secara intelektual, namun juga matang secara emosional dan kokoh secara spiritual. Profil sekolah yang progresif, ditunjang dengan berbagai pembaruan fasilitas, menjadikan MI Raden Rahmat sebagai madrasah pilihan bagi para orang tua yang menginginkan pendidikan terbaik bagi putra-putrinya.</p><p>Dengan memadukan Kurikulum Merdeka yang berbasis kompetensi dan Kurikulum Keislaman khas pesantren (Ahlussunnah wal Jamaah), MI Raden Rahmat menawarkan keunggulan komparatif yang tidak dimiliki oleh sekolah dasar pada umumnya. Di sini, pelajaran ilmu pengetahuan umum seperti matematika, sains, dan bahasa diintegrasikan dengan pemahaman agama yang inklusif, toleran, dan moderat. Anak-anak dididik untuk memiliki literasi digital yang mumpuni di abad ke-21, tanpa meninggalkan identitas keagamaan mereka yang berakar kuat pada nilai-nilai tradisi Islam nusantara.</p><p>Fasilitas yang kami tawarkan pun terus mengalami peningkatan dari tahun ke tahun. Ruang kelas yang nyaman dan representatif, laboratorium komputer standar, perpustakaan dengan koleksi buku beragam, serta area bermain dan olahraga yang luas menjadi instrumen penunjang yang memastikan kenyamanan proses belajar mengajar. Tenaga pendidik yang ada juga terus diberikan pelatihan dan upgrading skill untuk menjawab dinamika tantangan zaman, memastikan metode pengajaran selalu relevan, menyenangkan, dan merangsang daya nalar kritis siswa secara optimal.</p><p>Tidak hanya menitikberatkan pada aspek kognitif, madrasah ini juga sangat kaya dengan kegiatan ekstrakurikuler yang bertujuan untuk mengembangkan minat dan bakat siswa di berbagai bidang. Mulai dari kepramukaan (Pramuka), drumband, kesenian al-banjari, seni bela diri pencak silat Pagar Nusa, hingga bimbingan olimpiade MIPA dan bahasa Inggris. Semua wadah ini disediakan agar setiap potensi unik yang dimiliki oleh masing-masing anak mendapatkan tempat yang tepat untuk tumbuh dan berkembang secara maksimal.</p><p>Komitmen kami adalah menjadi mitra terpercaya bagi para orang tua dalam mengukir masa depan anak bangsa yang gemilang. Kami percaya bahwa setiap anak adalah bintang yang memiliki sinarnya sendiri. Di MI Raden Rahmat, kami tidak hanya mendidik, tetapi kami merawat, membimbing, dan memoles potensi tersebut agar kelak mereka bersinar terang membawa kemanfaatan yang seluas-luasnya di tengah masyarakat. Saksikan terus perjalanan kami melalui berbagai publikasi profil dan mari menjadi bagian dari keluarga besar yang inspiratif ini!</p>'
    ],
    [
        'title' => 'Pembiasaan Sholat Dhuha berjamaah dan Ngaos kitab Alala',
        'date' => '17 Feb 2026',
        'author' => 'miradenrahmatbalongbendo',
        'img' => 'api/ig_cache/DU3p1C9E9Mq.jpg',
        'content' => '<p>Pendidikan karakter di MI Raden Rahmat tidak hanya berhenti pada jargon, tetapi diejawantahkan dalam bentuk program pembiasaan harian yang terstruktur dan masif. Salah satu pilar utamanya adalah kegiatan Sholat Dhuha berjamaah yang diikuti dengan pengajian (ngaos) kitab kuning dasar, khususnya Kitab Alala. Kegiatan ini menjadi pondasi spiritual harian yang dijalankan sebelum aktivitas pembelajaran formal di kelas dimulai, membentuk ritme hidup disiplin dan kedekatan kepada Sang Pencipta pada awal hari.</p><p>Sholat Dhuha berjamaah yang diselenggarakan setiap pagi merupakan sarana yang sangat efektif untuk melatih kedisiplinan dan kekhusyukan anak-anak. Dalam pelaksanaannya, anak-anak diajarkan tata tertib beribadah di madrasah, mulai dari berwudhu dengan sempurna, merapikan barisan shaf shalat, hingga tatacara melantunkan dzikir dan doa pasca-shalat. Selain mendatangkan keberkahan dan kelapangan rezeki sebagaimana sabda Rasulullah SAW, ibadah sunnah ini memiliki efek psikologis yang menenangkan. Anak-anak terbiasa memulai hari dengan pikiran yang positif, jernih, dan jiwa yang tenteram, sehingga mereka jauh lebih siap dan fokus dalam menerima materi pelajaran selanjutnya.</p><p>Setelah shalat Dhuha, agenda dilanjutkan dengan kajian (ngaos) Kitab Alala (Alālā Tanālul \'Ilma). Kitab Alala adalah kitab nazham (puisi/syair berirama) ringkas yang telah turun-temurun menjadi pedoman dasar para santri pesantren di nusantara. Kitab ini secara spesifik berisi wejangan tentang akhlak, adab dalam menuntut ilmu, kriteria pendidik yang ideal, serta tata krama seorang pembelajar sejati terhadap ilmu dan gurunya. Melalui bimbingan ustadz-ustadzah, bait-bait syair Kitab Alala dilantunkan secara bersama-sama, diartikan, dan dijelaskan maknanya melalui konteks kehidupan sehari-hari siswa.</p><p>Makna dari kitab ini sangat dalam dan fundamental. Terdapat enam syarat penting bagi pelajar yang ingin memperoleh ilmu yang bermanfaat, yaitu: kecerdasan (dzaka\'), semangat paripurna (hirsh), bersabar (ishthibar), kecukupan bekal (bulghah), petunjuk/bimbingan guru (irsyadu ustadzin), dan ketekunan sepanjang masa (thulu zaman). Syarat-syarat inilah yang terus ditanamkan ke dalam alam bawah sadar siswa melalui metode pengajian sorogan maupun bandongan. Hasilnya, siswa tidak hanya mengejar nilai akademis secara instan, namun sangat menghargai proses pembelajaran itu sendiri dan meletakkan sikap hormat (ta\'dzim) kepada guru di tempat yang supreme.</p><p>Kolaborasi antara spiritualitas ibadah (Shalat Dhuha) dan keilmuan adab (Ngaos Kitab Alala) inilah yang menjadikan lulusan MI Raden Rahmat memiliki distingsi karakter pembeda. Mereka tangguh dalam menghadapi ujian, santun dalam bertutur sapa, dan tidak pernah angkuh meskipun telah menggapai berbagai prestasi gemilang. Budaya inilah yang akan terus kami pertahankan dan wariskan dari generasi ke generasi sebagai bentuk komitmen penyelenggaraan pendidikan berbasis Ahlussunnah wal Jamaah An-Nahdliyah.</p>'
    ]
];

$styleBlock = <<<HTML
<style>
    /* Override grid index.html untuk halaman berita agar full-width */
    html, body {
        height: auto !important;
        min-height: 100vh;
    }
    
    .main-wrapper {
        display: block !important;
        max-width: 1100px;
        margin: 40px auto !important;
        padding: 0 20px;
        float: none !important;
        width: 100% !important;
    }
    
    .full-article-card {
        display: flex;
        flex-direction: column;
        scroll-margin-top: 100px;
        margin-bottom: 60px;
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        transition: transform 0.3s ease;
    }
    
    .full-article-card:hover {
        transform: translateY(-5px);
    }
    .article-banner {
        height: 300px !important;
    }
    @media (min-width: 900px) {
        .full-article-card {
            flex-direction: row !important;
            align-items: stretch;
        }
        .article-banner {
            width: 40% !important;
            height: auto !important;
            min-height: 100%;
        }
        .article-body {
            width: 60%;
        }
        .article-footer {
            width: 100%;
            display: flex;
            margin-top: auto;
        }
    }
    
    @media (max-width: 899px) {
        .full-article-card .article-footer {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start !important;
        }
        .full-article-card h2 {
            font-size: 1.4rem !important;
        }
    }
</style>
HTML;

$mainContent = <<<HTML
{$styleBlock}
<main class="container main-wrapper">
    <div class="header-section" style="text-align: center; margin-bottom: 60px; padding-top: 20px;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 3rem; color: var(--primary); margin-bottom: 15px;">Berita & Artikel</h1>
        <div style="width: 80px; height: 4px; background: var(--accent); margin: 0 auto 25px;"></div>
        <p style="color: var(--text-muted); font-size: 1.2rem; max-width: 700px; margin: 0 auto; line-height: 1.6;">
            Informasi terbaru, kajian mendalam, dan berbagai kegiatan inspiratif dari keluarga besar <br><strong>BP3NU Raden Rahmat Balongbendo</strong>.
        </p>
    </div>
    
    <div class="articles-grid">
HTML;


foreach ($newsData as $index => $article) {
    $mainContent .= <<<HTML
        <article class="full-article-card" style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); display: flex; flex-direction: column;">
            <div class="article-banner" style="position: relative; width: 100%; height: 350px; background: #e0e0e0; overflow: hidden;">
                <img src="{$article['img']}" alt="Thumbnail Berita" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
            </div>
            
            <div class="article-content-wrapper" style="display: flex; flex-direction: column; width: 100%;">
                <div class="article-body" style="padding: 35px; flex: 1;">
                    <div class="article-meta" style="display: flex; gap: 20px; font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <span><i class="far fa-calendar-alt" style="color: var(--accent);"></i> {$article['date']}</span>
                        <span><i class="far fa-user" style="color: var(--accent);"></i> {$article['author']}</span>
                        <span><i class="far fa-folder-open" style="color: var(--accent);"></i> Berita Utama</span>
                    </div>
                    
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--text-dark); margin-bottom: 20px; line-height: 1.3;">{$article['title']}</h2>
                    
                    <div class="article-content" style="font-size: 1rem; color: #4b5563; line-height: 1.8; text-align: justify;">
                        {$article['content']}
                    </div>
                </div>
                
                <div class="article-footer" style="padding: 20px 35px; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; margin-top: auto;">
                    <div class="share-buttons" style="display: flex; gap: 10px;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-right: 10px;">Bagikan:</span>
                        <a href="#" style="color: #3b5998; font-size: 1.2rem;"><i class="fab fa-facebook-square"></i></a>
                        <a href="#" style="color: #25D366; font-size: 1.2rem;"><i class="fab fa-whatsapp-square"></i></a>
                        <a href="#" style="color: #1DA1F2; font-size: 1.2rem;"><i class="fab fa-twitter-square"></i></a>
                    </div>
                    <button style="background: var(--accent); color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Baca Berita Lainnya <i class="fas fa-arrow-up" style="margin-left: 5px;"></i>
                    </button>
                </div>
            </div>
        </article>
HTML;
}

$mainContent .= <<<HTML
        <!-- Pagination -->
        <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
            <span style="padding: 10px 15px; background: var(--primary); color: #fff; border-radius: 8px; font-weight: bold;">1</span>
            <a href="#" style="padding: 10px 15px; background: #fff; color: var(--text-dark); border: 1px solid #ddd; border-radius: 8px; text-decoration: none;">2</a>
            <a href="#" style="padding: 10px 15px; background: #fff; color: var(--text-dark); border: 1px solid #ddd; border-radius: 8px; text-decoration: none;">3</a>
            <a href="#" style="padding: 10px 15px; background: #fff; color: var(--text-dark); border: 1px solid #ddd; border-radius: 8px; text-decoration: none;"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
HTML;

// Combine
$finalHtml = $header . "\n" . $mainContent . "\n" . $footer;

// Fix absolute links back to homepage if necessary
$finalHtml = str_replace('href="#" class="ppdb-btn"', 'href="index.html" class="ppdb-btn"', $finalHtml);
$finalHtml = str_replace('<a href="#">Berita & Artikel</a>', '<a href="berita.html" class="active">Berita & Artikel</a>', $finalHtml);

file_put_contents('/mnt/webdata/htdocs/berita.html', $finalHtml);
echo "berita.html generated successfully.";
?>