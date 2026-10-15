@include('filament.resources.company-profile-submissions.history', [
    'current' => $getRecord(),
    'submissions' => $getRecord()->history(),
])
