<input type="password" name="{{ $input_name }}" autocomplete="new-password"
	id="{{ $input_id }}"|if="$input_id" class="password rx_ev_password" size="10"
	style="{{ $definition->style }}"|if="$definition->style"
	value="{{ strval($value) !== '' ? $value : $default }}"
	@required(toBool($definition->is_required))
	@disabled(toBool($definition->is_disabled))
	@readonly(toBool($definition->is_readonly))
/>
<span class="pass_show"><i class="bi bi-eye-slash"></i></span>
<style>
.rx_ev_password { width:250px !important;  max-width:100%;   padding-right: 30px !important;}
</style>
<script>
$(".pass_show").on("click", function () {
  const $passwordInput = $(this).prev();
  const type = $passwordInput.attr("type") === "password" ? "text" : "password";
  $passwordInput.attr("type", type);
  $(this).html(type === "password" ? "<i class='bi bi-eye-slash'></i>" : "<i class='bi bi-eye-fill'></i>");
});
</script>