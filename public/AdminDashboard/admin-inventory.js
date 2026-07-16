(function (app) {
  "use strict";

  var addStockForm = document.getElementById("addStockForm");
  var imageInput = document.getElementById("newProductImage");
  var imageName = document.getElementById("newProductImageName");
  var imagePlaceholder = document.getElementById("newProductPlaceholder");
  var imagePreview = document.getElementById("newProductPreview");
  var stockList = document.getElementById("stockList");
  var previewUrl = null;

  function refreshInventoryViews() {
    app.refresh("inventory");
    app.refresh("report");
    app.refresh("summary");
    app.refresh("audit");
  }

  function buildStockRow(item) {
    var row = document.createElement("form");
    var image = item.image_path
      ? '<img src="' + app.baseUrl + "/" + app.safeText(item.image_path) + '" alt="">'
      : '<span class="stock-image-missing">No image</span>';

    row.className = "edit-row stock-row";
    row.dataset.productId = item.product_id;
    row.dataset.variantId = item.variant_id;
    row.innerHTML =
      '<label><span>Product</span><div class="stock-product-cell">' + image +
      '<div><input name="name" value="' + app.safeText(item.product_name) + '" required>' +
      '<small class="stock-product-meta">Product #' + item.product_id + ' - ' +
      item.product_variant_count + ' variants</small></div></div></label>' +
      '<div class="variant-fields"><label><span>Color</span><input name="color" value="' +
      app.safeText(item.color) + '" maxlength="50" required></label>' +
      '<label><span>Size</span><input name="size" value="' +
      app.safeText(item.size) + '" maxlength="30" required></label></div>' +
      '<label><span>Category</span><input name="category" value="' + app.safeText(item.category_name) + '" required></label>' +
      '<label><span>Price</span><input type="number" name="price" min="0" step="0.01" value="' + Number(item.price).toFixed(2) + '" required></label>' +
      '<label><span>Stock</span><input type="number" name="stock" min="0" step="1" value="' + item.stock_quantity + '" required></label>' +
      '<div class="row-actions"><button type="submit" class="ghost-button">Update Variant</button>' +
      '<button type="button" class="danger-button delete-stock">Delete Product</button></div>';
    return row;
  }

  function loadInventory() {
    return app.api("stock").then(function (items) {
      stockList.innerHTML = "";
      items.forEach(function (item) {
        stockList.appendChild(buildStockRow(item));
      });
    }).catch(function (error) {
      app.showNotice("Could not load stock: " + error);
    });
  }

  function resetImagePreview() {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = null;
    imagePreview.removeAttribute("src");
    imagePreview.hidden = true;
    imagePlaceholder.hidden = false;
    imageName.textContent = "Choose a clear product photo";
  }

  imageInput.addEventListener("change", function () {
    resetImagePreview();
    var file = imageInput.files && imageInput.files[0];
    if (!file) return;

    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
      imageInput.value = "";
      app.showNotice("Choose a JPEG, PNG, or WebP image.");
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      imageInput.value = "";
      app.showNotice("Product images must be no larger than 5 MB.");
      return;
    }

    previewUrl = URL.createObjectURL(file);
    imagePreview.src = previewUrl;
    imagePreview.hidden = false;
    imagePlaceholder.hidden = true;
    imageName.textContent = file.name;
  });

  addStockForm.addEventListener("submit", function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var submitButton = document.getElementById("addStockButton");
    if (!form.elements.image.files.length) {
      app.showNotice("Choose a product image before adding the product.");
      return;
    }

    submitButton.disabled = true;
    submitButton.textContent = "Uploading product...";
    app.api("stock", { method: "POST", formData: new FormData(form) }).then(function () {
      app.showNotice("Product and image added.");
      form.reset();
      resetImagePreview();
      refreshInventoryViews();
    }).catch(function (error) {
      app.showNotice("Could not add product: " + error);
    }).then(function () {
      submitButton.disabled = false;
      submitButton.textContent = "Add Product";
    });
  });

  stockList.addEventListener("submit", function (event) {
    if (!event.target.classList.contains("stock-row")) return;
    event.preventDefault();
    var row = event.target;
    var details = {
      product_id: Number(row.dataset.productId),
      name: row.elements.name.value.trim(),
      category: row.elements.category.value.trim(),
      color: row.elements.color.value.trim(),
      size: row.elements.size.value.trim(),
      price: Number(row.elements.price.value),
      stock: Number(row.elements.stock.value),
    };

    app.api("variants", { method: "PUT", id: row.dataset.variantId, body: details }).then(function () {
      app.showNotice("Size/color variant updated.");
      refreshInventoryViews();
    }).catch(function (error) {
      app.showNotice("Could not update stock item: " + error);
    });
  });

  stockList.addEventListener("click", function (event) {
    if (!event.target.classList.contains("delete-stock")) return;
    var row = event.target.closest("[data-product-id]");
    var productName = row.elements.name.value.trim();
    if (!window.confirm('Delete the entire product "' + productName + '" and all of its variants?')) return;
    app.api("stock", { method: "DELETE", id: row.dataset.productId }).then(function () {
      app.showNotice("Product and all of its variants deleted.");
      refreshInventoryViews();
    }).catch(function (error) {
      app.showNotice("Could not delete stock item: " + error);
    });
  });

  app.registerLoader("inventory", loadInventory);
})(window.ParamAdmin);
