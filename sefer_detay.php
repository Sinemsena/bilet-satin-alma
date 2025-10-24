<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Sefer bulunamadı!");
}

$id = $_GET['id'];
$stmt = $db->prepare("SELECT r.*, f.name AS firm_name 
                      FROM routes r 
                      JOIN firms f ON r.firm_id = f.id
                      WHERE r.id = ?");
$stmt->execute([$id]);
$sefer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sefer) die("Sefer bulunamadı!");

// dolu koltukları çekelim
$stmt = $db->prepare("SELECT seat_number FROM tickets WHERE route_id=? AND status='active'");
$stmt->execute([$id]);
$doluKoltuklar = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $sefer['departure'] ?> → <?= $sefer['arrival'] ?> | <?= $sefer['firm_name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .seat-map {
            display: grid;
            grid-template-columns: repeat(4, 50px);
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        .seat {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s ease;
        }
        .seat.empty { background-color: #e9ecef; }
        .seat.taken { background-color: #dc3545; color: white; cursor: not-allowed; }
        .seat.selected { background-color: #28a745; color: white; }
        .legend { text-align: center; margin-top: 10px; }
        .legend span { margin-right: 15px; }
        .legend-box {
            display: inline-block;
            width: 15px; height: 15px;
            border-radius: 3px;
            margin-right: 5px;
        }
        .box-empty { background: #e9ecef; }
        .box-taken { background: #dc3545; }
        .box-selected { background: #28a745; }
    </style>
</head>
<body>

<div class="container py-5">
    <a href="index.php" class="btn btn-outline-secondary mb-4">← Ana Sayfaya Dön</a>

    <div class="card shadow-lg p-4">
        <div class="row">
            <div class="col-md-7">
                <h3 class="text-primary fw-bold"><?= $sefer['firm_name'] ?></h3>
                <p><strong><?= $sefer['departure'] ?></strong> → <strong><?= $sefer['arrival'] ?></strong></p>
                <p><?= $sefer['date'] ?> | <?= $sefer['time'] ?></p>
                <p><strong>Fiyat:</strong> <?= $sefer['price'] ?> ₺</p>
                <p><strong>Koltuk Sayısı:</strong> <?= $sefer['seats'] ?></p>
                <hr>

                <h5>Koltuk Seçimi</h5>
                <div id="seatMap" class="seat-map">
                    <?php
                    for ($i = 1; $i <= $sefer['seats']; $i++) {
                        $class = in_array($i, $doluKoltuklar) ? 'seat taken' : 'seat empty';
                        echo "<div class='$class' data-seat='$i'>$i</div>";
                    }
                    ?>
                </div>

                <div class="legend">
                    <span><span class="legend-box box-empty"></span> Boş</span>
                    <span><span class="legend-box box-taken"></span> Dolu</span>
                    <span><span class="legend-box box-selected"></span> Seçilen</span>
                </div>
            </div>

            <div class="col-md-5 border-start">
                <div class="p-3">
                    <h4 class="fw-bold mb-3 text-success">Satın Alma</h4>
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'user'): ?>
                        <form method="POST" action="bilet_al.php" id="buyForm">
                            <input type="hidden" name="route_id" value="<?= $sefer['id'] ?>">
                            <input type="hidden" name="seat_number" id="selectedSeat">
                            
                            <div class="mb-3">
                                <label for="coupon" class="form-label">İndirim Kuponu (isteğe bağlı)</label>
                                <input type="text" id="coupon" name="coupon_code" class="form-control" placeholder="Kupon kodunuzu girin">
                            </div>

                            <div class="alert alert-info" id="seatInfo">Lütfen bir koltuk seçiniz.</div>
                            <button type="submit" class="btn btn-success w-100 btn-lg" disabled id="buyBtn">Bileti Satın Al</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">
                            🎫 Bilet satın almak için <a href="login.php" class="alert-link">giriş yapın</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const seats = document.querySelectorAll('.seat.empty');
const seatInfo = document.getElementById('seatInfo');
const selectedSeatInput = document.getElementById('selectedSeat');
const buyBtn = document.getElementById('buyBtn');

seats.forEach(seat => {
    seat.addEventListener('click', () => {
        seats.forEach(s => s.classList.remove('selected'));
        seat.classList.add('selected');
        const seatNum = seat.getAttribute('data-seat');
        seatInfo.textContent = `Seçilen koltuk: ${seatNum}`;
        selectedSeatInput.value = seatNum;
        buyBtn.disabled = false;
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const couponInput = document.querySelector('#coupon_code');
    const checkBtn = document.createElement('button');
    const buyBtn = document.querySelector('#buyButton');
    const infoBox = document.createElement('div');

    checkBtn.textContent = 'Kuponu Kontrol Et';
    checkBtn.classList.add('btn', 'btn-outline-primary', 'mt-2');
    couponInput.insertAdjacentElement('afterend', checkBtn);
    couponInput.insertAdjacentElement('afterend', infoBox);

    checkBtn.addEventListener('click', function(e) {
        e.preventDefault();
        infoBox.innerHTML = '⏳ Kontrol ediliyor...';
        fetch('kupon_kontrol.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'coupon_code=' + encodeURIComponent(couponInput.value) +
                  '&route_id=' + document.querySelector('[name="route_id"]').value
        })
        .then(res => res.json())
        .then(data => {
            if (!data.valid) {
                infoBox.innerHTML = `<span style="color:red;">${data.message}</span>`;
                if (confirm("Kupon geçersiz. Kuponsuz devam etmek istiyor musunuz?")) {
                    buyBtn.disabled = false;
                } else {
                    buyBtn.disabled = true;
                }
            } else {
                infoBox.innerHTML = `<span style="color:green;">${data.message}</span>`;
                buyBtn.disabled = false;
            }
        });
    });
});
</script>

</body>
</html>
