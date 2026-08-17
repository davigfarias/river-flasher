---
paths:
  - 'app/Livewire/Forms/*.php'
---

# Forms

## Livewire Form objects double as input DTOs via toData()
Every Form object (e.g. CardForm) exposes a toData(): SomeDTO method that the owning Livewire component calls to get the DTO an Action expects. Components never pass loose form properties into an Action — always form->toData(). Dynamic validation rules that need DB context (e.g. Rule::in of the current token's deck slugs) belong in the Form's rules() method, which may query scoped by session('access_token_id').
