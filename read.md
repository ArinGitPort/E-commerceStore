<script>
$(document).ready(function() {
    // Quick view click handler
    $(document).on('click', '.quick-view', function() {
        const productId = $(this).data('product-id');
        showQuickView(productId);
    });

    // Show quick view modal
    function showQuickView(productId) {
        $.ajax({
            url: 'quick-view.php',
            type: 'GET',
            data: { product_id: productId },
            success: function(response) {
                $('#quickViewModal .modal-content').html(response);
                $('#quickViewModal').modal('show');
                
                // Initialize modal functionality
                initQuickViewModal();
            },
            error: function(xhr, status, error) {
                console.error('Error loading quick view:', error);
                alert('Error loading product details. Please try again.');
            }
        });
    }

    // Initialize quick view modal functionality
    function initQuickViewModal() {
        // Quantity buttons
        $('.modal-quantity-btn').off('click').on('click', function() {
            const input = $(this).siblings('.modal-quantity-input');
            let value = parseInt(input.val());
            const max = parseInt(input.attr('max')) || 999;
            const min = parseInt(input.attr('min')) || 1;

            if ($(this).hasClass('modal-plus') && value < max) {
                input.val(value + 1);
            } else if ($(this).hasClass('modal-minus') && value > min) {
                input.val(value - 1);
            }
        });

        // Form submission
        $('.add-to-cart-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const formData = form.serialize();
            
            $.ajax({
                url: 'shop.php', // Submit to the same page
                type: 'POST',
                data: formData,
                success: function(response) {
                    // Close the modal
                    $('#quickViewModal').modal('hide');
                    
                    // Show success message
                    showAlert('Item added to cart!', 'success');
                    
                    // Optional: Update cart count in navbar
                    updateCartCount();
                },
                error: function(xhr, status, error) {
                    console.error('Error adding to cart:', error);
                    showAlert('Error adding to cart. Please try again.', 'danger');
                }
            });
        });
    }

    // Helper function to show alerts
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show text-center" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Prepend to container or show in a fixed position
        $('.shop-container').prepend(alertHtml);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 3000);
    }

    // Optional: Update cart count in navbar
    function updateCartCount() {
        $.get('get-cart-count.php', function(count) {
            $('.cart-count').text(count);
        });
    }
});
</script>