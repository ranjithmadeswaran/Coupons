<div class="coupon">
    <label for="coupon_code" class="fs-13 text-dark fw-bold">Apply Coupon :</label>
    <div class="input-group input-group-sm">
        <input type="text" class="form-control form-control-sm" id="coupon_code" name="coupon_code" placeholder="{{ __('Enter coupon code') }}">
        <button type="button" class="input-group-text btn-dark fw-medium d-block btn-sm" id="coupon_btn">ADD</button>
        <button type="button" class="input-group-text btn-danger fw-medium d-none btn-sm" id="coupon_remove_btn" style="background: red;">Remove</button>
    </div>
    <span class="fs-10" id="coupon_code_error"></span>
</div>