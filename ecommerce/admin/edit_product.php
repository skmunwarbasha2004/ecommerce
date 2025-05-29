<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get product details
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Product ID is missing.");
}

$product_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

// Handle form submission
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $product['image']; // Default to existing image

    // Check if a new image is uploaded
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../images/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Validate image file type
        if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
            echo "<p style='color:red; text-align:center;'>Only JPG, JPEG, and PNG files are allowed.</p>";
            $uploadOk = 0;
        }

        // Validate file size (limit: 2MB)
        if ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
            echo "<p style='color:red; text-align:center;'>File size must be less than 2MB.</p>";
            $uploadOk = 0;
        }

        if ($uploadOk) {
            // Move uploaded file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image
                if (!empty($product['image']) && file_exists("../images/" . $product['image'])) {
                    unlink("../images/" . $product['image']);
                }
                $image = $image_name; // Update image name in the database
            } else {
                echo "<p style='color:red; text-align:center;'>Failed to upload image.</p>";
            }
        }
    }

    // Update product details
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
    $result = $stmt->execute([$name, $price, $description, $image, $product_id]);

    if ($result) {
        header("Location: manage_products.php");
        exit();
    } else {
        echo "<p style='color:red; text-align:center;'>Failed to update product.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            color: #555;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        img {
            display: block;
            width: 100px;
            margin: 10px 0;
        }
        .btn-back {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Product</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="name">Product Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($product['name']); ?>" required>

        <label for="price">Price</label>
        <input type="number" name="price" id="price" value="<?= $product['price']; ?>" required step="0.01">

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($product['description']); ?></textarea>

        <label>Current Image</label><br>
        <img src="../images/<?= htmlspecialchars($product['image']); ?>" alt="Product Image">

        <label for="image">Upload New Image (Optional)</label>
        <input type="file" name="image" id="image">

        <button type="submit" name="update">Update Product</button>
    </form>

    <a href="manage_products.php" class="btn-back">Back to Manage Products</a>
</div>

</body>
</html>
