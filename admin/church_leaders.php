<?php
/**
 * church_leaders.php 
 * this page will display all church leaders according to their category
 */
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

//Get All leaders grouped by category
$categories = [
    'Pastoral' => 'Pastoral Team',
    'Elders' => 'Church Elders',
    'Deacons' => 'Deacons',
    'Deaconesses' => 'Deaconesses',
    'Department' => 'Department Leaders',
    'Ministry' => 'Ministry Leaders'

];

$leaders_by_category = [];

foreach($categories as $key => $label){
    $query = "SELECT * FROM church_leaders
             WHERE category = :category AND status = 'active'
             ORDER BY order_position ASC, name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([':category' => $key]);
    $leaders_by_category[$key] = [
        'label' => $label,
        'leaders' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];         
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Leaders - Kisii University SDA Church</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --red:#e74c3c;
            --red-dark:#c0392b;
            --gray-dark:#333;
            --gray-light:#f5f5f5;
            --gray-medium:#e0e0e0;
        }
        body{
            font-family:'Inter',sans-serif;
            background:var(--gray-light);
        }
        .leaders-header{
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:white;
            padding:60px 0 40px;
            text-align:center;

        }
        .leaders-header h1{
            font-size:2.5rem;
            font-weight:700;
        }
        .section-title{
            font-size: 1.8rem;
            font-weight: 700;
            margin: 50px 0 30px;
            padding-bottom:15px;
            border-bottom:3px solid var(--red);
            display:inline-block;
            color:var(--gray-dark);
        }
        .leader-card{
            background:white;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 5px 20px rgba(0,0,0,0.08);
            transition:transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height:100%;
        }
        .leader-card:hover{
            transform:translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .leader-photo-wrapper{
            position:relative;
            overflow:hidden;
            background:var(--gray-light);
            height:280px;
        }
        .leader-photo{
            width:100%;
            height:100%;
            object-fit:cover;
            transition:transform 0.3s;
        }
        .leader-card:hover,.leader-photo{
            transform:scake(1.05);
        }
        .leader-info{
            padding:20px;
            text-align:center;
        }
        .leader-name{
            font-size:1.25rem;
            font-weight:700;
            color:var(--gray-dark);
            margin-bottom:5px;
        }
        .leader-title{
            color:var(--red);
            font-weight:600;
            font-weight:0.85rem;
            margin-bottom:15px;
            letter-spacing:0.5px;
        }
        .contact-buttons{
            display:flex;
            gap:10px;
            justify-content:center;
            margin-top:15px;
            flex-wrap:wrap;
        }
        .contact-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 16px;
            border-radius:30px;
            text-decoration:none;
            font-size:0.8rem;
            font-weight:500;
            transition: all 0.2s;
        }
        .contact-btn i{
            font-size:1rem;
        }
        .contact-btn:hover{
            transform:translateY(-2px);
        }
        .btn-call,.btn-wa{
            background:#25d366;
            color:white;
        }
        .btn-call:hover,.btn-wa:hover{
            background:#128c7e;
            color:white;
        }
        .btn-email{
            background:var(--gray-dark);
            color:white;
        }
        .btn-email:hover{
            background:#555;
            color:white;
        }
        .no-leaders{
            text-align:center;
            padding:60px;
            background:white;
            border-radius:16px;
            color:#999;
        }
        @media(max-width: 768px){
            .leaders-header h1{
                font-size:1.8rem;
            }
            .section-title{
                font-size:1.4rem;
            }
            .leader-photo-wrapper{
                height:220px;
            }
        }
        </style>
</head>
<body>
    
<!----Header-->
<div class="leaders-header">
    <div class="container">
        <i class="fas fa-church fa-3x mb-3"></i>
        <h1>Our Church Leaders</h1>
        <p class="lead">Meet dedicated team serving Kisii University SDA Church</p>

</div>
</div>

<div class="container py-4">

<!--Pastoral Team-->
<?php if(count($leaders_by_category['Pastoral']['leaders'])>0):?>
    <div class="text-center">
        <h2 class="section-title">
            <i class="fas fa-pastor"></i>Pastoral Team
</h2>
</div>

<div class="row">
<?php foreach($leaders_by_category['Pastoral']['leaders'] as $leader):?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="leader-card">
            <div class="leader-photo-wrapper">
                <img src="<?php echo !empty($leader['photo']) ? 'uploads/leaders/' . $leader['photo'] : 'images/default-avatar.png';?>"
                class="leader-photo" alt="<?php echo htmlspecialchars($leader['name']);?>"oneerror="this.src='images/default-avatar.png'">

</div>
<div class="leader-info">
    <h3 class="leader-name"><?php echo htmlspecialchars($leader['name']);?></h3>
    <div class="leader-title"><?php echo htmlspecialchars($leader['title']);?></div>
    <?php if($leader['bio']): ?>
        <p class="small text-muted mt-2"><?php echo htmlspecialchars(substr($leader['bio'],0,100)); ?></p>
        <?php endif; ?>
        <div class="contact-buttons">
            <?php if($leader['phone']): ?>
                <a href="tel:<?php echo $leader['phone']; ?>" class="contact-btn btn-call" title="Call">
                    <i class="fas fa-phone"></i>Call 
                </a>
                <?php endif; ?>

                <?php if($leader['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','',$leader['whatsapp']); ?>"
                    class="contact-btn btn-wa" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>WhatsApp</a>
                    <?php endif; ?>
                    <?php if($leader['email']):?>
                    <a href="mailto:<?php echo $leader['email']; ?>" class="contact-btn btn-email" title="Email">
                        <i class="fas fa-envelope"></i>Email</a>
                        <?php endif; ?>

    </div>
</div>
</div>
</div>
<?php endforeach; ?>

<!----Church Elders-->
<?php if(count($leaders_by_category['Elders']['leaders']) > 0): ?>
    <div class="text-center">
        <h2 class="section-title">
            <i class="fas fa-users"></i>Church Elders
</h2>
</div>
<div class="row">
    <?php foreach($leaders_by_category['Elders']['leaders'] as $leader): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="leader-card">
                <div class="leader-photo-wrapper" style="height: 220px;">
                    <img src="<?php echo !empty($leader['photo']) ? 'uploads/leaders/' . $leader['photo'] : 'images/default-avatar.png'; ?>" 
                         class="leader-photo" 
                         alt="<?php echo htmlspecialchars($leader['name']); ?>"
                         onerror="this.src='images/default-avatar.png'">
                </div>
    <div class="leader-info">
        <h3 class="leader-name"><?php echo htmlspecialchars($leader['name']);?></h3>
        <div class="leader-title"><?php echo htmlspecialchars($leader['title']);?></div>
        <div class="contact-buttons">
            <?php if($leader['phone']): ?>
                        <a href="tel:<?php echo $leader['phone']; ?>" class="contact-btn btn-call btn-sm" title="Call">
                            <i class="fas fa-phone"></i>
                        </a>
                        <?php endif; ?>
                        <?php if($leader['whatsapp']):?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]','',$leader['whatsapp']); ?>" class="contact-btn btn-wa btn-sm" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
            </a>
            <?php endif; ?>
    </div>
    </div>
    </div>
    </div>
    <?php endforeach;?>
    </div>
    <?php endif; ?>

    <!--Deacons-->
    
<?php if(count($leaders_by_category['Deacons']['leaders']) > 0): ?>
    <div class="text-center">
        <h2 class="section-title">
            <i class="fas fa-holding-heart"></i>Deacons
</h2>
</div>
<div class="row">
    <?php foreach($leaders_by_category['Deacons']['leaders'] as $leader): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="leader-card">
                <div class="leader-photo-wrapper" style="height:220px;">
                    <img src="<?php echo !empty($leader['photo']) ? 'uploads/leaders/'. $leader['photo'] : 'images/default-avatar.png'; ?>" class="leader-photo" alt="<php echo htmlspecialchars($leader['name']);?>" oneerror="this.src='images/default-avatar.png'">

    </div>
    <div class="leader-info">
        <h3 class="leader-name"><?php echo htmlspecialchars($leader['name']);?></h3>
        <div class="leader-title"><?php echo htmlspecialchars($leader['title']);?></div>
        <div class="contact-buttons">
            <?php if($leader['phone']): ?>
                <a href="tel:<?php echo $leader['phone'];?>" class="contact-btn btn-call btn-sm" title="Call">
                    <i class="fas fa-phone"></i>
            </a>
            <?php endif;?>
            <?php if($leader['whatsapp']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]','',$leader['whatsapp']); ?>" class="contact-btn btn-wa btn-sm" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
            </a>
            <?php endif; ?>
    </div>
    </div>
    </div>
    </div>
    <?php endforeach;?>
    </div>
    <?php endif; ?>

    <!--Deaconnesses-->
    
    <?php if(count($leaders_by_category['Deaconesses']['leaders']) > 0): ?>
    <div class="text-center">
        <h2 class="section-title">
            <i class="fas fa-holding-heart"></i>Deacons
</h2>
</div>
<div class="row">
    <?php foreach($leaders_by_category['Deaconesses']['leaders'] as $leader): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="leader-card">
                <div class="leader-photo-wrapper" style="height:220px;">
                    <img src="<?php echo !empty($leader['photo']) ? 'uploads/leaders/'. $leader['photo'] : 'images/default-avatar.png'; ?>" class="leader-photo" alt="<php echo htmlspecialchars($leader['name']);?>" oneerror="this.src='images/default-avatar.png'">

    </div>
    <div class="leader-info">
        <h3 class="leader-name"><?php echo htmlspecialchars($leader['name']);?></h3>
        <div class="leader-title"><?php echo htmlspecialchars($leader['title']);?></div>
        <div class="contact-buttons">
            <?php if($leader['phone']): ?>
                <a href="tel:<?php echo $leader['phone'];?>" class="contact-btn btn-call btn-sm" title="Call">
                    <i class="fas fa-phone"></i>
            </a>
            <?php endif;?>
            <?php if($leader['whatsapp']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]','',$leader['whatsapp']); ?>" class="contact-btn btn-wa btn-sm" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
            </a>
            <?php endif; ?>
    </div>
    </div>
    </div>
    </div>
    <?php endforeach;?>
    </div>
    <?php endif; ?>

    <!--Department Leaders--->
    <?php if(count($leaders_by_category['Department']['leaders']) > 0): ?>
    <div class="text-center">
        <h2 class="section-title">
            <i class="fas fa-building"></i>Department Leaders
</h2>
</div>
<div class="row">
    <?php foreach($leaders_by_category['Department']['leaders'] as $leader): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="leader-card">
                <div class="leader-photo-wrapper" style="height:220px;">
                    <img src="<?php echo !empty($leader['photo']) ? 'uploads/leaders/'. $leader['photo'] : 'images/default-avatar.png'; ?>" class="leader-photo" alt="<php echo htmlspecialchars($leader['name']);?>" oneerror="this.src='images/default-avatar.png'">

    </div>
    <div class="leader-info">
        <h3 class="leader-name"><?php echo htmlspecialchars($leader['name']);?></h3>
        <div class="leader-title"><?php echo htmlspecialchars($leader['title']);?></div>
        <div class="contact-buttons">
            <?php if($leader['phone']): ?>
                <a href="tel:<?php echo $leader['phone'];?>" class="contact-btn btn-call btn-sm" title="Call">
                    <i class="fas fa-phone"></i>
            </a>
            <?php endif;?>
            <?php if($leader['whatsapp']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]','',$leader['whatsapp']); ?>" class="contact-btn btn-wa btn-sm" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
            </a>
            <?php endif; ?>
    </div>
    </div>
    </div>
    </div>
    <?php endforeach;?>
    </div>
    <?php endif; ?>

    <!--Ministry Leaders--->
    <?php if(count($leaders_by_category['Ministry']['leaders']) > 0): ?>
    <div class="text-center">
        <h2 class="section-title">
            <i class="fas fa-music"></i> Ministry Leaders
</h2>
</div>
<div class="row">
    <?php foreach($leaders_by_category['Ministry']['leaders'] as $leader): ?>
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="leader-card">
                <div class="leader-photo-wrapper" style="height:220px;">
                    <img src="<?php echo !empty($leader['photo']) ? 'uploads/leaders/'. $leader['photo'] : 'images/default-avatar.png'; ?>" class="leader-photo" alt="<php echo htmlspecialchars($leader['name']);?>" oneerror="this.src='images/default-avatar.png'">

    </div>
    <div class="leader-info">
        <h3 class="leader-name"><?php echo htmlspecialchars($leader['name']);?></h3>
        <div class="leader-title"><?php echo htmlspecialchars($leader['title']);?></div>
        <div class="contact-buttons">
            <?php if($leader['phone']): ?>
                <a href="tel:<?php echo $leader['phone'];?>" class="contact-btn btn-call btn-sm" title="Call">
                    <i class="fas fa-phone"></i>
            </a>
            <?php endif;?>
            <?php if($leader['whatsapp']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]','',$leader['whatsapp']); ?>" class="contact-btn btn-wa btn-sm" target="_blank" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
            </a>
             <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
<?php endif; ?>