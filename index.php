<?php
    // Pull in the database connection
    require_once 'includes/dbconnection.php';
    // Initialise the connection 
    $db = getDatabaseConnection();
    // Get Header
    include 'includes/header.php';
    // Display Main
?>

<section class="hero" id="heroBanner">
    <img src="../../Graphics/DockTower.webp" alt="Dock Tower" class="hero-bg-img">
    <div id="heroContent">
        Buy Local<br>Sell Local<br>Be Local
    </div>
    <div id="heroCategories" class="category-scroll">
        <button class="btn category-btn">Electronics</button>
        <button class="btn category-btn">Fashion</button>
        <button class="btn category-btn">Home</button>
        <button class="btn category-btn">Books</button>
        <button class="btn category-btn">Tools</button>
        <button class="btn category-btn">Sports</button>
        <button class="btn category-btn">Toys</button>
        <button class="btn category-btn">Other</button>
        <button class="btn category-btn">Automotive</button>
        <button class="btn category-btn">Garden</button>
        <button class="btn category-btn">Fishing & Marine</button>
        <button class="btn category-btn">Antiques</button>
        <button class="btn category-btn">Pet Supplies</button>
        <button class="btn category-btn">Health & Beauty</button>
    </div>
</section>

<div class="login-register" id="loginDiv">
    <button class="btn me-2" id="loginBtn">Login</button>
    <button class="btn btn-outline-light">Register</button>
</div>

<div class="search-bar" id="searchBar">
    <input type="text" id="searchInput" class="form-control" placeholder="Search for products...">
    <button class="btn" id="browseBtn">Browse Categories</button>
</div>

<div class="product-area" id="productArea">
    Search Results...
</div>

<?php 
    // Get Footer
    include 'includes/footer.php';
?>

<script>
  const browseBtn = document.getElementById('browseBtn');
  const heroBanner = document.getElementById('heroBanner');
  const heroContent = document.getElementById('heroContent');
  const heroCategories = document.getElementById('heroCategories');
  const loginDiv = document.getElementById('loginDiv');
  const loginBtn = document.getElementById('loginBtn');
  const navList = document.getElementById('navList');
  const searchBar = document.getElementById('searchBar');
  const productArea = document.getElementById('productArea');
  const searchInput = document.getElementById('searchInput');

  const PRODUCTS = [
    { img: "../../Graphics/pc1.jpg", desc: "Windows 11 8th Gen Mini PC Lenovo ThinkCentre M720q — 16GB DDR4 • 512GB NVMe • Good - Refurbished" },
    { img: "../../Graphics/pc2.jpg", desc: "Dell OptiPlex Micro 5070 — i5-9500T • 16GB RAM • 256GB M.2 — Excellent Refurbished Condition" },
    { img: "../../Graphics/pc3.jpg", desc: "HP ProDesk 600 G4 Mini — Intel i7-8700T • 32GB DDR4 • 512GB SSD — Grade A Refurbished" },
    { img: "../../Graphics/pc4.jpg", desc: "Lenovo ThinkCentre M920q — i5-9600T • 8GB DDR4 • 256GB M.2 SSD — Good - Refurbished" }
  ];

  searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    if (q === "") {
      productArea.innerHTML = "Search Results...";
      productArea.style.color = "#b8c2cc";
      return;
    }
    productArea.innerHTML = "";
    productArea.style.color = "#e4e8eb";
    PRODUCTS.forEach(p => {
      if (p.desc.toLowerCase().includes(q)) {
        const card = document.createElement("div");
        card.className = "result-card";
        card.innerHTML = `<img src="${p.img}"><div class="result-desc">${p.desc}</div>`;
        productArea.appendChild(card);
      }
    });
    if (productArea.innerHTML === "") productArea.innerHTML = "<em>No results found.</em>";
  });

    browseBtn.addEventListener('click', () => {
        heroBanner.classList.toggle('show-categories');
        browseBtn.classList.toggle('btn-active');
    });

  loginBtn.addEventListener('click', () => {
    loginDiv.style.display = 'none';
    ['Dashboard','Basket','Logout'].forEach(item => {
      const li = document.createElement('li');
      li.classList.add('nav-item');
      li.innerHTML = `<a class="nav-link" href="#">${item}</a>`;
      navList.appendChild(li);
      if(item === 'Logout') li.addEventListener('click', () => location.reload());
    });
  });
</script>
</body>
</html>
