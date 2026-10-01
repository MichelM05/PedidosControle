@props(['action', 'message' => 'Esta ação não pode ser desfeita.', 'title' => 'Excluir', 'label' => 'Excluir', 'class' => 'btn btn-actions btn-actions-delete'])

<form action="{{ $action }}" method="POST"
      data-confirm="{{ $message }}" data-confirm-title="{{ $title }}" data-confirm-button="{{ $label }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="{{ $class }}">{{ $label }}</button>
</form>
