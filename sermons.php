
<?php
/**
 * sermons.php - Sermon Page with YouTube Carousel + Featured Blog Cards
 * Features: YouTube carousel, Featured sermons with social share, Latest sermon with auto YouTube thumbnail
 * Color Scheme: White, Gray, Red only
 */

require_once '../UPGRADED KSUSDA WEBSITE/admin/config/sermon_config.php';

$sermonManager = new SermonManager();

// Get YouTube sermons from database
$youtubeSermons = $sermonManager->getSermons(50);

// Featured Blog Sermons (7 sermons on various topics)
$featuredSermons = [
    [
        'id' => 'featured_1',
        'title' => 'Building a Christ-Centered Marriage',
        'speaker' => 'Pastor John Mwangi',
        'date' => '2024-04-14',
        'scripture' => 'Ephesians 5:22-33',
        'content' => '<p>A comprehensive message for couples on how to build a marriage that honors God. Learn the biblical roles of husband and wife, effective communication skills, conflict resolution strategies, and how to keep Christ at the center of your relationship.</p>
        <p><strong>Key Points:</strong></p>
        <ul>
            <li>Understanding God\'s design for marriage</li>
            <li>The power of prayer as a couple</li>
            <li>Financial unity in marriage</li>
            <li>Raising children in a godly home</li>
        </ul>
        <p>This sermon includes practical advice for newlyweds and couples who have been married for decades. Topics covered include understanding God\'s design for marriage, the power of prayer as a couple, financial unity, and raising children in a godly home.</p>
        <p><strong>Conclusion:</strong> A Christ-centered marriage is possible when both partners submit to God\'s authority and love each other sacrificially.</p>',
        'topic' => 'Family',
        'image' => 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=800&h=500&fit=crop',
        'read_time' => '8 min read'
    ],
    [
        'id' => 'featured_2',
        'title' => 'Divine Healing: God\'s Will for Your Health',
        'speaker' => 'Elder Sarah Chebet',
        'date' => '2024-04-07',
        'scripture' => 'James 5:14-16',
        'content' => '<p>Understanding God\'s heart for healing is essential for every believer. This sermon explores biblical promises about physical healing, the role of faith in recovery, and the importance of prayer for the sick.</p>
        <p><strong>Key Scriptures:</strong> Isaiah 53:5, 1 Peter 2:24, Psalm 103:2-3</p>
        <p><strong>Main Points:</strong></p>
        <ul>
            <li>Healing is part of the atonement</li>
            <li>The role of faith in receiving healing</li>
            <li>Praying for the sick with authority</li>
            <li>Medical treatment and divine healing working together</li>
        </ul>
        <p>Includes testimonies of healing and guidance for those struggling with chronic illness. God still heals today!</p>',
        'topic' => 'Health',
        'image' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8bnV0cml0aW9ufGVufDB8fDB8fHww',
        'read_time' => '7 min read'
    ],
    [
        'id' => 'featured_3',
        'title' => 'The Power of Unconditional Love',
        'speaker' => 'Pastor Mary Wanjiku',
        'date' => '2024-03-31',
        'scripture' => '1 Corinthians 13:4-8',
        'content' => '<p>Love is the greatest virtue and the hallmark of true Christianity. Discover what true biblical love looks like in action - patient, kind, not envious or boastful.</p>
        <p><strong>Characteristics of God\'s Love:</strong></p>
        <ul>
            <li>Love is patient and kind</li>
            <li>Love does not envy or boast</li>
            <li>Love is not proud or self-seeking</li>
            <li>Love keeps no record of wrongs</li>
        </ul>
        <p>Learn how to love others even when it\'s difficult, including your enemies and those who have hurt you. This sermon challenges believers to move beyond sentimental love to sacrificial love that reflects God\'s own heart.</p>',
        'topic' => 'Love',
        'image' => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?w=800&h=500&fit=crop',
        'read_time' => '6 min read'
    ],
    [
        'id' => 'featured_4',
        'title' => 'Raising Godly Children in a Broken World',
        'speaker' => 'Elder Trevor Davis',
        'date' => '2025-05-18',
        'scripture' => 'Psalm 127:3-4',
        'content' => '<p>There is a battle raging for the hearts and minds of our children.We live in a world where biblical values are constantly being undermined,and the pressure to conform is relentless.The culture is loud,persuasive,and unrelenting.And as parents , we feel it.</p>
        <p><i>How do we raise children who love Jesus when the world is against Him? How do we equip them to stand when everything around them screams the opposite?Are we doing enough?Are we even capable?</i></p>
        <p><strong>But here is the good news:</strong> We are not raising our children alone. God himself has entrusted them to us, and He has given us everything we need to guide them.<u>We are not powerless. We are not alone. And we are not without plan.</u></p>
        <p>Corrie ten Boom once said:<strong><u>"Worry does not empty tomorrow of its sorrow,it empties today of its strength."</u></strong>

        <p>Worry will not protect our children. But trust in God will. We can wring our hands in fear, or we can fold them in prayer.We can stress over every little thing, or we can anchor ourselves in the word of God.</p>


        <p><strong>Three Key Things for Raising Children of God</strong></p>
        <ul>
            <li>See Your Children as God see them</li>
            <li>Train your children in truth , not just morality</li>
            <li>Model christlike character daily</li>
        
        </ul>
        <p>Includes advice for single parents and grandparents raising grandchildren. Your children are a heritage from the Lord!</p>',
        'topic' => 'Family',
        'image' => 'https://plus.unsplash.com/premium_photo-1691752881339-d78da354ee7e?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8Y2hpbGRyZW58ZW58MHx8MHx8fDA%3D',
        'read_time' => '9 min read'
    ],
    [
        'id' => 'featured_5',
        'title' => 'Living a Life of Purpose',
        'speaker' => 'Pastor John Mwangi',
        'date' => '2024-03-17',
        'scripture' => 'Jeremiah 29:11',
        'content' => '<p>God has a specific plan and purpose for your life. Discover how to find your purpose, use your spiritual gifts for God\'s glory, and make a lasting difference in your generation.</p>
        <p><strong>Finding Your Purpose:</strong></p>
        <ul>
            <li>Understanding the concept of calling</li>
            <li>How to discern God\'s will</li>
            <li>Overcoming fear and doubt</li>
            <li>Stepping out in faith</li>
        </ul>
        <p>Includes practical exercises for identifying your strengths and passions, and guidance for those considering ministry or career changes. You were created for a purpose!</p>',
        'topic' => 'Purpose',
        'image' => 'https://images.unsplash.com/photo-1504805572947-34fad45aed93?w=800&h=500&fit=crop',
        'read_time' => '7 min read'
    ],
    [
        'id' => 'featured_6',
        'title' => 'Forgiveness: Setting Yourself Free',
        'speaker' => 'Pastor Mary Wanjiku',
        'date' => '2024-03-10',
        'scripture' => 'Matthew 6:14-15',
        'content' => '<p>Unforgiveness is a prison that keeps you bound to past hurts. Learn how to forgive others as Christ forgave you, and experience the freedom that comes from letting go.</p>
        <p><strong>Steps to Forgiveness:</strong></p>
        <ul>
            <li>Acknowledge the hurt</li>
            <li>Choose to forgive (it\'s a decision, not a feeling)</li>
            <li>Release the offender to God</li>
            <li>Pray for those who hurt you</li>
        </ul>
        <p>This sermon addresses the physical, emotional, and spiritual consequences of unforgiveness. Includes guidance for those struggling to forgive themselves.</p>',
        'topic' => 'Love',
        'image' => 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=800&h=500&fit=crop',
        'read_time' => '8 min read'
    ],
    [
        'id' => 'featured_7',
        'title' => 'Health According to the Bible',
        'speaker' => 'Dr. James Otieno',
        'date' => '2024-03-03',
        'scripture' => '1 Corinthians 6:19-20',
        'content' => '<p>Your body is the temple of the Holy Spirit. Learn biblical principles for healthy living including diet, exercise, rest, and avoiding harmful substances.</p>
        <p><strong>Biblical Health Principles:</strong></p>
        <ul>
            <li>Plant-based nutrition from Genesis 1:29</li>
            <li>The importance of rest and Sabbath</li>
            <li>Managing stress through prayer</li>
            <li>Overcoming addictions through God\'s power</li>
        </ul>
        <p>Explore the connection between spirituality and physical health, the health benefits of following God\'s laws, and practical steps for improving your lifestyle.</p>',
        'topic' => 'Health',
        'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=500&fit=crop',
        'read_time' => '10 min read'
    ]
];

// Combine all YouTube sermons for carousel
$carouselSermons = $youtubeSermons;

// Get latest sermon - AUTOMATICALLY USES THE FIRST YOUTUBE SERMON FROM DATABASE
$latestSermon = !empty($carouselSermons) ? $carouselSermons[0] : null;

// Function to get reliable YouTube thumbnail URL
function getYouTubeThumbnail($videoId, $quality = 'mqdefault') {
    // quality options: 'default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault'
    // mqdefault (320x180) always works, hqdefault (480x360) usually works
    return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
}

// Get unique speakers for filter
$allSpeakers = array_unique(array_column($youtubeSermons, 'preacher'));
$allSpeakers = array_values(array_filter($allSpeakers));
sort($allSpeakers);

// Get unique topics for filter
$topics = ['Health', 'Love', 'Family', 'Purpose'];
sort($topics);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sermons — Kisii University Seventh-day Adventist Church. Listen to inspiring messages and teachings from our pastor.">
  <title>Sermons— Kisii University SDA Church</title>
  <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" type="image/png" href="../images/kisiilogo.png">
  <style>
    .site-footer a {
      text-decoration: none;
    }
    .site-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <header class="site-header header-solid" role="banner">
    <div class="header-top">
      <div class="header-logo">
        <a href="index.html" aria-label="Kisii University SDA Church Home">
          <img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-white.svg" alt="Seventh-day Adventist Logo" class="sda-logo-img">
          <span class="logo-text">Kisii University Seventh-day Adventist Church</span>
        </a>
      </div>
      <div class="header-right-top">
        <a href="giving.html" class="giving-pill giving-pill-desktop">❤️ Giving</a>
        <div class="header-social-icons">
          <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
          <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
        </div>
      </div>
    </div>
    <nav class="header-nav" aria-label="Primary Navigation">
      <div class="desktop-nav">
        <div class="nav-item">
          <a href="about/index.html">About Us <span class="chevron-down">▾</span></a>
          <div class="desktop-dropdown">
            <a href="about/giving.html">Giving</a><a href="about/history.html">History</a><a href="about/in-the-news.html">In the News</a><a href="about/our-pastor.html">Our Pastor</a><a href="about/sabbath-services.html">Sabbath Services</a><a href="about/what-sda-believe.html">What SDAs Believe</a><a href="about/worship-with-us.html">Worship With Us</a><a href="about/potluck.html">Potluck &amp; Fellowship</a><a href="about/special-events.html">Special Events</a><a href="about/schools.html">Schools &amp; Education</a><a href="about/nearby-churches.html">Nearby SDA Churches</a>
          </div>
        </div>
        <div class="nav-item"><a href="events.html">Calendar</a></div>
        <div class="nav-item">
          <a href="ministries/index.html">Ministries <span class="chevron-down">▾</span></a>
          <div class="desktop-dropdown">
            <a href="ministries/community-outreach.html">Community Outreach</a><a href="ministries/health.html">Health Ministry</a><a href="ministries/youth-and-children.html">Youth &amp; Children</a><a href="ministries/bible-studies.html">Bible Studies</a><a href="ministries/evangelism.html">Evangelism</a>
            <a href="ministries/new-zion.html">New Zion</a>
            <a href="ministries/christ-messengers.html">Christ Messengers</a>
            <a href="ministries/first-fruits.html">First Fruits</a>
            <a href="ministries/the-sentinels.html">The Sentinels</a>
            <a href="ministries/hom.html">HOM — Hands On Mission</a>
          </div>
        </div>
        <div class="nav-item"><a href="giving.html">Online Giving</a></div>
        <div class="nav-item"><a href="contact.html">Contact Us</a></div>
      </div>
      <div class="nav-actions">
        <button class="nav-icon-btn hamburger-btn" aria-label="Open Menu" title="Menu"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
        <button class="nav-icon-btn header-search-btn" aria-label="Search" title="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
      </div>
      <div class="header-search-inline" role="search">
        <form action="search.html" method="get"><input type="text" name="s" placeholder="Search..." aria-label="Search"><button type="submit" aria-label="Search">🔍</button></form>
      </div>
    </nav>
  </header>
  <div class="menu-overlay" aria-hidden="true"></div>
  <nav class="slide-menu" role="navigation" aria-label="Mobile Navigation">
    <div class="menu-header"><button class="menu-close-btn" aria-label="Close Menu">&times;</button></div>
    <div class="menu-search"><form action="search.html" method="get"><input type="text" name="s" placeholder="Search..." aria-label="Search"><button type="submit" aria-label="Search">🔍</button></form></div>
    <div class="menu-giving"><a href="giving.html" class="giving-pill">❤️ Giving</a></div>
    <div class="menu-nav">
      <div class="menu-nav-item">
        <div class="menu-nav-link"><a href="about/index.html" style="color:inherit;text-decoration:none;flex:1;">About Us</a><button class="menu-chevron" aria-label="Expand About Us submenu">▼</button></div>
        <div class="menu-submenu"><a href="about/giving.html">Giving</a><a href="about/history.html">History</a><a href="about/in-the-news.html">In the News</a><a href="about/our-pastor.html">Our Pastor</a><a href="about/sabbath-services.html">Sabbath Services</a><a href="about/what-sda-believe.html">What SDAs Believe</a><a href="about/worship-with-us.html">Worship With Us</a><a href="about/potluck.html">Potluck &amp; Fellowship</a><a href="about/special-events.html">Special Events</a><a href="about/schools.html">Schools &amp; Education</a><a href="about/nearby-churches.html">Nearby SDA Churches</a></div>
      </div>
      <div class="menu-nav-item"><a href="events.html" class="menu-nav-link">Calendar</a></div>
      <div class="menu-nav-item">
        <div class="menu-nav-link"><a href="ministries/index.html" style="color:inherit;text-decoration:none;flex:1;">Ministries</a><button class="menu-chevron" aria-label="Expand Ministries submenu">▼</button></div>
        <div class="menu-submenu"><a href="ministries/community-outreach.html">Community Outreach</a><a href="ministries/health.html">Health Ministry</a><a href="ministries/youth-and-children.html">Youth &amp; Children</a><a href="ministries/bible-studies.html">Bible Studies</a><a href="ministries/evangelism.html">Evangelism</a>
          <a href="ministries/new-zion.html">New Zion</a>
          <a href="ministries/christ-messengers.html">Christ Messengers</a>
          <a href="ministries/first-fruits.html">First Fruits</a>
          <a href="ministries/the-sentinels.html">The Sentinels</a>
          <a href="ministries/hom.html">HOM — Hands On Mission</a></div>
      </div>
      <div class="menu-nav-item"><a href="giving.html" class="menu-nav-link">Online Giving</a></div>
      <div class="menu-nav-item"><a href="contact.html" class="menu-nav-link">Contact Us</a></div>
      <div class="menu-nav-item"><a href="announcements.html" class="menu-nav-link">Announcements</a></div>
      <div class="menu-nav-item"><a href="livestream.html" class="menu-nav-link">Livestream</a></div>
      <div class="menu-nav-item"><a href="bulletin.html" class="menu-nav-link">Bulletin</a></div>
      <div class="menu-nav-item"><a href="food.html" class="menu-nav-link">Food Assistance</a></div>
    </div>
    <div class="menu-footer">
      <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
      <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
    </div>
  </nav>

 


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sermons - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ========================================
           COLOR SCHEME: WHITE, GRAY, RED ONLY
        ======================================== */
        :root {
            --white: #FFFFFF;
            --gray-light: #F5F5F5;
            --gray-medium: #E0E0E0;
            --gray-dark: #666666;
            --gray-darker: #333333;
            --red: #E74C3C;
            --red-dark: #C0392B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-light);
            color: var(--gray-darker);
        }

        /* ========================================
           HEADER
        ======================================== */
        .sermon-header {
            background: var(--white);
            padding: 60px 0 40px;
            text-align: center;
            border-bottom: 1px solid var(--gray-medium);
        }

        .sermon-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-darker);
            margin-bottom: 10px;
        }

        .sermon-header h1 i {
            color: var(--red);
            margin-right: 10px;
        }

        .sermon-header p {
            color: var(--gray-dark);
            font-size: 1.1rem;
        }

        /* ========================================
           LATEST SERMON SECTION (YouTube Thumbnail Only)
        ======================================== */
        .latest-sermon-section {
            background: var(--white);
            padding: 40px 0;
            margin: 30px 0;
            border: 1px solid var(--gray-medium);
            border-radius: 16px;
        }

        .latest-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
        }

        .latest-badge {
            background: var(--red);
            color: var(--white);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 12px;
        }

        .latest-thumbnail {
            position: relative;
            min-height: 280px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            cursor: pointer;
            background-color: #1a1a1a;
        }

        .play-icon-large {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 4rem;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: transform 0.2s;
            opacity: 0.9;
        }

        .latest-thumbnail:hover .play-icon-large {
            transform: translate(-50%, -50%) scale(1.1);
        }

        /* ========================================
           SOCIAL SHARE BUTTONS
        ======================================== */
        .share-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-medium);
        }

        .share-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-darker);
            margin-bottom: 10px;
        }

        .share-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .share-btn i { font-size: 1rem; }
        .share-btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .share-whatsapp { background: #25D366; color: white; }
        .share-facebook { background: #1877F2; color: white; }
        .share-twitter { background: #1DA1F2; color: white; }
        .share-email { background: var(--gray-dark); color: white; }
        .share-copy { background: var(--gray-medium); color: var(--gray-darker); }

        .card-share-buttons {
            display: flex;
            gap: 12px;
            margin: 12px 0;
            padding: 8px 0;
            border-top: 1px solid var(--gray-light);
            border-bottom: 1px solid var(--gray-light);
        }

        .card-share-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: transform 0.2s;
            padding: 5px;
        }

        .card-share-btn:hover { transform: scale(1.1); }
        .card-share-btn.whatsapp { color: #25D366; }
        .card-share-btn.facebook { color: #1877F2; }
        .card-share-btn.twitter { color: #1DA1F2; }
        .card-share-btn.email { color: var(--gray-dark); }
        .card-share-btn.copy { color: var(--gray-dark); }

        .copy-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--gray-darker);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 2000;
            animation: fadeInOut 2s ease;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(20px); }
            15% { opacity: 1; transform: translateY(0); }
            85% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(20px); }
        }

        /* ========================================
           FILTER BAR
        ======================================== */
        .filter-bar {
            background: var(--white);
            padding: 20px 0;
            border-bottom: 1px solid var(--gray-medium);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid var(--gray-medium);
            border-radius: 8px;
            font-size: 0.9rem;
            background: var(--white);
            color: var(--gray-darker);
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--red);
        }

        .filter-btn {
            padding: 8px 20px;
            border: 1px solid var(--gray-medium);
            border-radius: 25px;
            background: var(--white);
            color: var(--gray-dark);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
        }

        .filter-btn.active {
            background: var(--red);
            border-color: var(--red);
            color: var(--white);
        }

        .filter-btn:hover:not(.active) {
            border-color: var(--red);
            color: var(--red);
        }

        /* ========================================
           CAROUSEL STYLES
        ======================================== */
        .carousel-section {
            background: var(--white);
            padding: 40px 0;
            margin: 30px 0;
            border: 1px solid var(--gray-medium);
            border-radius: 16px;
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
            padding: 10px 0;
        }

        .carousel-track {
            display: flex;
            gap: 25px;
            animation: scrollLeft 50s linear infinite;
            width: fit-content;
        }

        .carousel-section:hover .carousel-track {
            animation-play-state: paused;
        }

        @keyframes scrollLeft {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .carousel-card {
            width: 320px;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--gray-medium);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            flex-shrink: 0;
        }

        .carousel-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .carousel-thumbnail {
            position: relative;
            height: 180px;
            background-color: var(--gray-light);
        }

        .carousel-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .carousel-card:hover .play-overlay {
            opacity: 1;
        }

        .play-overlay i {
            font-size: 2.5rem;
            color: var(--white);
        }

        .youtube-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--red);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .carousel-content {
            padding: 15px;
        }

        .carousel-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-darker);
            margin-bottom: 6px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .carousel-speaker {
            font-size: 0.8rem;
            color: var(--red);
            font-weight: 500;
        }

        /* ========================================
           FEATURED BLOG CARDS
        ======================================== */
        .featured-section {
            padding: 40px 0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-darker);
            margin: 0 0 30px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--red);
            display: inline-block;
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .blog-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--gray-medium);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .blog-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .blog-thumbnail {
            position: relative;
            height: 200px;
            background-color: var(--gray-light);
        }

        .blog-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .topic-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--red);
            color: var(--white);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .blog-content {
            padding: 20px;
        }

        .blog-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gray-darker);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .blog-meta {
            display: flex;
            gap: 15px;
            font-size: 0.75rem;
            color: var(--gray-dark);
            margin-bottom: 12px;
        }

        .blog-meta i {
            margin-right: 4px;
        }

        .blog-excerpt {
            font-size: 0.85rem;
            color: var(--gray-dark);
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more {
            color: var(--red);
            font-size: 0.80rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius:20px;
            border:none;
            padding:5px;
            margin-left:5px;
        }

        .read-more:hover {
            color: var(--red-dark);
        }

        .scripture-ref {
            background: var(--gray-light);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            color: var(--gray-dark);
            display: inline-block;
            margin-top: 10px;
        }

        /* ========================================
           MODAL STYLES
        ======================================== */
        .blog-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .blog-modal.active {
            display: flex;
        }

        .modal-container {
            width: 90%;
            max-width: 900px;
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            background: var(--gray-darker);
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.8rem;
            cursor: pointer;
        }

        .close-modal:hover {
            transform: scale(1.1);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-thumbnail {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .modal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-medium);
        }

        .modal-meta-item {
            font-size: 0.85rem;
            color: var(--gray-dark);
        }

        .modal-meta-item i {
            margin-right: 6px;
            color: var(--red);
        }

        .modal-content-text {
            font-size: 1rem;
            line-height: 1.7;
            color: var(--gray-darker);
        }

        .modal-content-text p {
            margin-bottom: 15px;
        }

        .modal-content-text ul, .modal-content-text ol {
            margin-bottom: 15px;
            padding-left: 20px;
        }

        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }

        .video-modal.active {
            display: flex;
        }

        .video-container {
            width: 90%;
            max-width: 1000px;
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
        }

        .video-header {
            padding: 15px 20px;
            background: var(--gray-darker);
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .close-video {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.8rem;
            cursor: pointer;
        }

        .no-results {
            text-align: center;
            padding: 60px;
            background: var(--white);
            border-radius: 16px;
            color: var(--gray-dark);
        }

        @media (max-width: 768px) {
            .blog-grid { grid-template-columns: 1fr; }
            .sermon-header h1 { font-size: 1.8rem; }
            .filter-bar .row > div { margin-bottom: 10px; }
            .carousel-card { width: 280px; }
            .modal-meta { gap: 10px; }
            .latest-thumbnail { min-height: 200px; }
        }
    </style>
</head>
<body>

<!-- ========================================
     HEADER
======================================== -->
<div class="sermon-header">
    <div class="container">
        <h1> Our Sermons</h1>
        <p>Watch, read, and grow in God's word</p>
    </div>
</div>

<!-- ========================================
     LATEST SERMON SECTION (USES YOUTUBE THUMBNAIL ONLY)
======================================== -->
<?php if($latestSermon && isset($latestSermon['youtube_id'])): ?>
<?php 
// Generate the YouTube thumbnail URL - using hqdefault (480x360) which ALWAYS works
$youtubeThumbUrl = "https://img.youtube.com/vi/{$latestSermon['youtube_id']}/hqdefault.jpg";
?>
<div class="latest-sermon-section">
    <div class="container">
        <div class="latest-card">
            <div class="row g-0">
                <div class="col-md-5 latest-thumbnail" 
                     style="background-image: url('<?php echo $youtubeThumbUrl; ?>'); background-size: cover; background-position: center; background-repeat: no-repeat; cursor: pointer;"
                     onclick="openVideoModal('<?php echo $latestSermon['youtube_id']; ?>', '<?php echo htmlspecialchars($latestSermon['title']); ?>')">
                    <div class="play-icon-large">
                        <i class="fas fa-play-circle"></i>
                    </div>
                </div>
                <div class="col-md-7 p-4">
                    <span class="latest-badge"><i class="fas fa-star"></i> Latest Sermon</span>
                    <h2 class="mt-2"><?php echo htmlspecialchars($latestSermon['title']); ?></h2>
                    <p class="text-muted mb-2">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($latestSermon['preacher']); ?>
                        <i class="fas fa-calendar ms-3"></i> <?php echo date('F j, Y', strtotime($latestSermon['sermon_date'])); ?>
                    </p>
                    <?php if(isset($latestSermon['scripture']) && $latestSermon['scripture']): ?>
                        <span class="scripture-ref"><i class="fas fa-bible"></i> <?php echo htmlspecialchars($latestSermon['scripture']); ?></span>
                    <?php endif; ?>
                    <p class="mt-3"><?php echo htmlspecialchars(substr($latestSermon['description'] ?? 'Watch this powerful sermon from our latest service.', 0, 200)); ?>...</p>
                    <button class="btn" style="background: var(--red); color: white; border-radius: 25px; padding: 8px 25px; margin-top: 10px;" onclick="openVideoModal('<?php echo $latestSermon['youtube_id']; ?>', '<?php echo htmlspecialchars($latestSermon['title']); ?>')">
                        <i class="fas fa-play"></i> Watch Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================
     FILTER BAR
======================================== -->
<div class="filter-bar">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <select id="speakerFilter" class="filter-select w-100">
                    <option value="all">All Speakers</option>
                    <?php foreach($allSpeakers as $speaker): ?>
                        <option value="<?php echo htmlspecialchars($speaker); ?>"><?php echo htmlspecialchars($speaker); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <select id="topicFilter" class="filter-select w-100">
                    <option value="all">All Topics</option>
                    <?php foreach($topics as $topic): ?>
                        <option value="<?php echo $topic; ?>"><?php echo $topic; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button class="filter-btn w-100 active" id="filterAll">All Sermons</button>
                    <button class="filter-btn w-100" id="filterRecent">Last 20 Days</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     YOUTUBE CAROUSEL SECTION
======================================== -->
<div class="carousel-section">
    <div class="container">
        <h3 class="section-title"><i class="fab fa-youtube text-red"></i> YouTube Sermons</h3>
    </div>
    <div class="carousel-container">
        <div class="carousel-track" id="carouselTrack"></div>
    </div>
</div>

<!-- ========================================
     FEATURED BLOG SERMONS SECTION
======================================== -->
<div class="featured-section">
    <div class="container">
        <h3 class="section-title"><i class="fas fa-book-open text-red"></i> Featured Sermons</h3>
        <div class="blog-grid" id="blogGrid"></div>
    </div>
</div>

<!-- ========================================
     BLOG MODAL
======================================== -->
<div id="blogModal" class="blog-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">Sermon Title</h3>
            <button class="close-modal" onclick="closeBlogModal()">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalImage" class="modal-thumbnail" src="" alt="Sermon image">
            <div class="modal-meta">
                <span class="modal-meta-item"><i class="fas fa-user"></i> <span id="modalSpeaker"></span></span>
                <span class="modal-meta-item"><i class="far fa-calendar-alt"></i> <span id="modalDate"></span></span>
                <span class="modal-meta-item"><i class="fas fa-bible"></i> <span id="modalScripture"></span></span>
                <span class="modal-meta-item"><i class="fas fa-clock"></i> <span id="modalReadTime"></span></span>
                <span class="modal-meta-item"><i class="fas fa-tag"></i> <span id="modalTopic"></span></span>
            </div>
            <div class="modal-content-text" id="modalContent"></div>
            
            <div class="share-section">
                <div class="share-title"><i class="fas fa-share-alt"></i> Share this sermon:</div>
                <div class="share-buttons">
                    <button class="share-btn share-whatsapp" onclick="shareCurrentModalSermon('whatsapp')"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                    <button class="share-btn share-facebook" onclick="shareCurrentModalSermon('facebook')"><i class="fab fa-facebook-f"></i> Facebook</button>
                    <button class="share-btn share-twitter" onclick="shareCurrentModalSermon('twitter')"><i class="fab fa-twitter"></i> Twitter</button>
                    <button class="share-btn share-email" onclick="shareCurrentModalSermon('email')"><i class="fas fa-envelope"></i> Email</button>
                    <button class="share-btn share-copy" onclick="copyCurrentModalLink()"><i class="fas fa-link"></i> Copy Link</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     VIDEO MODAL
======================================== -->
<div id="videoModal" class="video-modal">
    <div class="video-container">
        <div class="video-header">
            <h3 id="videoTitle" style="margin: 0; font-size: 1rem;">Now Playing</h3>
            <button class="close-video" onclick="closeVideoModal()">&times;</button>
        </div>
        <div class="video-wrapper">
            <iframe id="youtubeFrame" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
</div>


<footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-main">
        <div class="footer-info">
          <h4>Kisii University Seventh-day Adventist Church</h4>
          <p><a href="https://www.google.com/maps/search/Kisii+University+Kenya" target="_blank">Kisii University Campus, Kisii, Kenya</a><br><a href="tel:+254700000000">+254 700 000 000</a></p>
          <p style="margin-top: 0.25rem;"><a href="mailto:info@kisiiuniversitysdachurch.org">info@kisiiuniversitysdachurch.org</a></p>
          <div class="footer-social">
            <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Facebook</a>
            <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> YouTube</a>
          </div>
        </div>
        <div class="footer-sda-logo"><img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-black.svg" alt="Seventh-day Adventist Church" style="height: 55px;"></div>
      </div>
      <div class="footer-bottom">
        <p>Copyright &copy; 2026 Kisii University Seventh-day Adventist Church.</p>
        <p><a href="#">Privacy Policy</a> &nbsp;|&nbsp; <a href="#">Copyright Policy</a></p>
      </div>
    </div>
  </footer>
  <div class="cookie-banner" role="alert">
    <div class="cookie-inner">
      <p>This site uses cookies to provide you with the best web experience.</p>
      <div class="cookie-buttons"><button class="cookie-accept">Accept</button><button class="cookie-reject">Reject</button></div>
    </div>
  </div>
  <script src="js/faq.js"></script>
  <script src="js/main.js"></script>
>


<script>
// ========================================
// DATA STORAGE
// ========================================
const youtubeSermons = <?php echo json_encode($youtubeSermons); ?>;
const featuredSermons = <?php echo json_encode($featuredSermons); ?>;

let currentSpeaker = 'all';
let currentTopic = 'all';
let currentTimeFilter = 'all';
let currentShareSermon = null;

// ========================================
// BUILD CAROUSEL
// ========================================
function buildCarousel() {
    let filtered = [...youtubeSermons];
    
    if(currentSpeaker !== 'all') {
        filtered = filtered.filter(s => (s.preacher || '').toLowerCase() === currentSpeaker.toLowerCase());
    }
    
    if(currentTimeFilter === 'recent') {
        const twentyDaysAgo = new Date();
        twentyDaysAgo.setDate(twentyDaysAgo.getDate() - 20);
        filtered = filtered.filter(s => new Date(s.sermon_date) >= twentyDaysAgo);
    }
    
    const track = document.getElementById('carouselTrack');
    if (!track) return;
    
    if(filtered.length === 0) {
        track.innerHTML = '<div class="no-results" style="width: 100%; text-align: center; margin: 20px;">No YouTube sermons match your filters.</div>';
        return;
    }
    
    let html = '';
    filtered.forEach(sermon => { html += createCarouselCard(sermon); });
    filtered.forEach(sermon => { html += createCarouselCard(sermon); });
    if(filtered.length < 6) {
        filtered.forEach(sermon => { html += createCarouselCard(sermon); });
    }
    
    track.innerHTML = html;
    const duration = Math.max(40, track.children.length * 1.2);
    track.style.animationDuration = `${duration}s`;
}

function createCarouselCard(sermon) {
    const thumbnailUrl = `https://img.youtube.com/vi/${sermon.youtube_id}/hqdefault.jpg`;
    const date = new Date(sermon.sermon_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    
    return `
        <div class="carousel-card" onclick="openVideoModal('${sermon.youtube_id}', '${escapeHtml(sermon.title)}')">
            <div class="carousel-thumbnail">
                <img src="${thumbnailUrl}" alt="${escapeHtml(sermon.title)}" loading="lazy">
                <div class="play-overlay"><i class="fas fa-play-circle"></i></div>
                <span class="youtube-badge"><i class="fab fa-youtube"></i> YouTube</span>
            </div>
            <div class="carousel-content">
                <h4 class="carousel-title">${escapeHtml(sermon.title)}</h4>
                <div class="carousel-speaker"><i class="fas fa-user"></i> ${escapeHtml(sermon.preacher)}</div>
                <div class="sermon-meta" style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.7rem; color: var(--gray-dark);">
                    <span><i class="far fa-calendar-alt"></i> ${date}</span>
                    <span><i class="fas fa-eye"></i> ${sermon.views || 0} views</span>
                </div>
            </div>
        </div>
    `;
}

// ========================================
// BUILD BLOG GRID
// ========================================
function buildBlogGrid() {
    let filtered = [...featuredSermons];
    if(currentTopic !== 'all') {
        filtered = filtered.filter(s => s.topic.toLowerCase() === currentTopic.toLowerCase());
    }
    
    const grid = document.getElementById('blogGrid');
    if (!grid) return;
    
    if(filtered.length === 0) {
        grid.innerHTML = '<div class="no-results">No featured sermons match your filters.</div>';
        return;
    }
    
    grid.innerHTML = filtered.map(sermon => createBlogCard(sermon)).join('');
}

function createBlogCard(sermon) {
    const date = new Date(sermon.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = sermon.content;
    const plainText = tempDiv.textContent || tempDiv.innerText || '';
    const excerpt = plainText.substring(0, 150) + '...';
    
    return `
        <div class="blog-card">
            <div class="blog-thumbnail" onclick="openBlogModal('${sermon.id}')">
                <img src="${sermon.image}" alt="${escapeHtml(sermon.title)}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1438032005730-c779502df39b?w=800&h=500&fit=crop'">
                <span class="topic-badge">${sermon.topic}</span>
            </div>
            <div class="blog-content">
                <h4 class="blog-title" onclick="openBlogModal('${sermon.id}')">${escapeHtml(sermon.title)}</h4>
                <div class="blog-meta" onclick="openBlogModal('${sermon.id}')">
                    <span><i class="fas fa-user"></i> ${escapeHtml(sermon.speaker)}</span>
                    <span><i class="far fa-calendar-alt"></i> ${date}</span>
                    <span><i class="fas fa-clock"></i> ${sermon.read_time}</span>
                </div>
                <p class="blog-excerpt" onclick="openBlogModal('${sermon.id}')">${escapeHtml(excerpt)}</p>
                
                <div class="card-share-buttons">
                    <button class="card-share-btn whatsapp" onclick="event.stopPropagation(); shareFeaturedSermon('whatsapp', '${sermon.id}')" title="Share on WhatsApp"><i class="fab fa-whatsapp"></i></button>
                    <button class="card-share-btn facebook" onclick="event.stopPropagation(); shareFeaturedSermon('facebook', '${sermon.id}')" title="Share on Facebook"><i class="fab fa-facebook-f"></i></button>
                    <button class="card-share-btn twitter" onclick="event.stopPropagation(); shareFeaturedSermon('twitter', '${sermon.id}')" title="Share on Twitter"><i class="fab fa-twitter"></i></button>
                    <button class="card-share-btn email" onclick="event.stopPropagation(); shareFeaturedSermon('email', '${sermon.id}')" title="Share via Email"><i class="fas fa-envelope"></i></button>
                    <button class="card-share-btn copy" onclick="event.stopPropagation(); copySermonLink('${escapeHtml(sermon.title)}', '${sermon.id}')" title="Copy Link"><i class="fas fa-link"></i></button>
                </div>
                
                <div class="scripture-ref" onclick="openBlogModal('${sermon.id}')"><i class="fas fa-bible"></i> ${sermon.scripture}</div>
                <button class="read-more" onclick="openBlogModal('${sermon.id}')">Read Full Sermon <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    `;
}

// ========================================
// VIDEO FUNCTIONS
// ========================================
function openVideoModal(videoId, title) {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('youtubeFrame');
    document.getElementById('videoTitle').textContent = title;
    iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('youtubeFrame');
    modal.classList.remove('active');
    iframe.src = '';
    document.body.style.overflow = '';
}

// ========================================
// BLOG MODAL FUNCTIONS
// ========================================
function openBlogModal(sermonId) {
    const sermon = featuredSermons.find(s => s.id === sermonId);
    if(!sermon) return;
    currentShareSermon = sermon;
    
    document.getElementById('modalTitle').textContent = sermon.title;
    document.getElementById('modalSpeaker').textContent = sermon.speaker;
    document.getElementById('modalDate').textContent = new Date(sermon.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('modalScripture').textContent = sermon.scripture;
    document.getElementById('modalReadTime').textContent = sermon.read_time;
    document.getElementById('modalTopic').textContent = sermon.topic;
    document.getElementById('modalImage').src = sermon.image;
    document.getElementById('modalContent').innerHTML = sermon.content;
    
    document.getElementById('blogModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeBlogModal() {
    document.getElementById('blogModal').classList.remove('active');
    document.body.style.overflow = '';
}

// ========================================
// SHARE FUNCTIONS
// ========================================
function shareFeaturedSermon(platform, sermonId) {
    const sermon = featuredSermons.find(s => s.id === sermonId);
    if(!sermon) return;
    const text = `${sermon.title} - by ${sermon.speaker}`;
    const url = window.location.href;
    
    const platforms = {
        whatsapp: () => window.open(`https://wa.me/?text=${encodeURIComponent(text + ' - ' + url)}`, '_blank'),
        facebook: () => window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(sermon.title)}`, '_blank', 'width=600,height=400'),
        twitter: () => window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out this sermon: ' + sermon.title)}&url=${encodeURIComponent(url)}`, '_blank', 'width=600,height=400'),
        email: () => {
            const subject = `Sermon: ${sermon.title}`;
            const body = `I wanted to share this sermon with you:\n\nTitle: ${sermon.title}\nSpeaker: ${sermon.speaker}\nScripture: ${sermon.scripture}\n\nRead it here: ${url}`;
            window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    };
    if(platforms[platform]) platforms[platform]();
}

function shareCurrentModalSermon(platform) {
    if(!currentShareSermon) return;
    shareFeaturedSermon(platform, currentShareSermon.id);
}

function copySermonLink(title, sermonId) {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => showNotification(`Link to "${title}" copied!`))
        .catch(() => {
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showNotification(`Link to "${title}" copied!`);
        });
}

function copyCurrentModalLink() {
    if(!currentShareSermon) return;
    copySermonLink(currentShareSermon.title, currentShareSermon.id);
}

function showNotification(message) {
    const existing = document.querySelector('.copy-notification');
    if(existing) existing.remove();
    const notification = document.createElement('div');
    notification.className = 'copy-notification';
    notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 2000);
}

function applyFilters() { buildCarousel(); buildBlogGrid(); }
function escapeHtml(text) { if (!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }

// ========================================
// EVENT LISTENERS
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    buildCarousel();
    buildBlogGrid();
    
    document.getElementById('speakerFilter').addEventListener('change', (e) => { currentSpeaker = e.target.value; applyFilters(); });
    document.getElementById('topicFilter').addEventListener('change', (e) => { currentTopic = e.target.value; applyFilters(); });
    document.getElementById('filterAll').addEventListener('click', () => {
        currentTimeFilter = 'all';
        document.getElementById('filterAll').classList.add('active');
        document.getElementById('filterRecent').classList.remove('active');
        applyFilters();
    });
    document.getElementById('filterRecent').addEventListener('click', () => {
        currentTimeFilter = 'recent';
        document.getElementById('filterRecent').classList.add('active');
        document.getElementById('filterAll').classList.remove('active');
        applyFilters();
    });
    
    const carouselContainer = document.querySelector('.carousel-container');
    if(carouselContainer) {
        let touchStartX = 0;
        carouselContainer.addEventListener('touchstart', (e) => {
            const track = document.querySelector('.carousel-track');
            if(track) track.style.animationPlayState = 'paused';
        });
        carouselContainer.addEventListener('touchend', (e) => {
            const track = document.querySelector('.carousel-track');
            if(track) track.style.animationPlayState = 'running';
        });
    }
});

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeBlogModal(); closeVideoModal(); } });
document.getElementById('blogModal').addEventListener('click', (e) => { if (e.target === document.getElementById('blogModal')) closeBlogModal(); });
document.getElementById('videoModal').addEventListener('click', (e) => { if (e.target === document.getElementById('videoModal')) closeVideoModal(); });
</script>

</body>
</html>