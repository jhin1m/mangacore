<?php

namespace Ophim\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $chapterId = $this->get('id') ?? request()->route('id');
        
        return [
            'manga_id' => 'required|exists:mangas,id',
            'chapter_number' => [
                'required',
                'numeric',
                'min:0',
                Rule::unique('chapters')->where(function ($query) {
                    return $query->where('manga_id', $this->manga_id);
                })->ignore($chapterId)
            ],
            'title' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'volume_id' => 'nullable|exists:volumes,id',
            'volume_number' => 'nullable|integer|min:1',
            'published_at' => 'nullable|date',
            'is_premium' => 'boolean',
            'pages_upload' => 'nullable|array',
            'pages_upload.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB max
            'zip_upload' => 'nullable|file|mimes:zip|max:102400' // 100MB max
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'manga_id' => 'manga',
            'chapter_number' => 'chapter number',
            'volume_id' => 'volume',
            'volume_number' => 'volume number',
            'published_at' => 'publish date',
            'is_premium' => 'premium status',
            'pages_upload' => 'page images',
            'pages_upload.*' => 'page image',
            'zip_upload' => 'ZIP file'
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'manga_id.required' => 'Please select a manga for this chapter.',
            'manga_id.exists' => 'The selected manga does not exist.',
            'chapter_number.required' => 'Chapter number is required.',
            'chapter_number.numeric' => 'Chapter number must be a valid number.',
            'chapter_number.min' => 'Chapter number must be at least 0.',
            'chapter_number.unique' => 'A chapter with this number already exists for the selected manga.',
            'title.max' => 'Chapter title cannot exceed 255 characters.',
            'slug.max' => 'Chapter slug cannot exceed 255 characters.',
            'volume_id.exists' => 'The selected volume does not exist.',
            'volume_number.integer' => 'Volume number must be a valid integer.',
            'volume_number.min' => 'Volume number must be at least 1.',
            'published_at.date' => 'Publish date must be a valid date.',
            'is_premium.boolean' => 'Premium status must be true or false.',
            'pages_upload.array' => 'Page uploads must be an array of files.',
            'pages_upload.*.image' => 'Each page file must be a valid image.',
            'pages_upload.*.mimes' => 'Page images must be in JPEG, JPG, PNG, GIF, or WebP format.',
            'pages_upload.*.max' => 'Each page image cannot exceed 10MB.',
            'zip_upload.file' => 'ZIP upload must be a valid file.',
            'zip_upload.mimes' => 'ZIP file must have a .zip extension.',
            'zip_upload.max' => 'ZIP file cannot exceed 100MB.'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation for chapter numbering within volume
            if ($this->volume_id && $this->volume_number) {
                $volume = \Ophim\Core\Models\Volume::find($this->volume_id);
                if ($volume && $volume->volume_number != $this->volume_number) {
                    $validator->errors()->add('volume_number', 'Volume number must match the selected volume.');
                }
            }

            // Validate that at least one upload method is provided for new chapters
            if (!$this->get('id') && !$this->hasFile('pages_upload') && !$this->hasFile('zip_upload')) {
                $validator->errors()->add('pages_upload', 'Please upload page images or a ZIP file for the chapter.');
            }

            // Validate ZIP file contents if uploaded
            if ($this->hasFile('zip_upload')) {
                $this->validateZipContents($validator);
            }

            // Validate published_at is not in the past for scheduled chapters
            if ($this->published_at && $this->published_at < now() && !$this->get('id')) {
                $validator->errors()->add('published_at', 'Publish date cannot be in the past for new chapters.');
            }
        });
    }

    /**
     * Validate ZIP file contents
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function validateZipContents($validator)
    {
        try {
            $zipFile = $this->file('zip_upload');
            $zip = new \ZipArchive();
            
            if ($zip->open($zipFile->getPathname()) === TRUE) {
                $imageCount = 0;
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    // Skip directories and system files
                    if (substr($filename, -1) === '/' || strpos($filename, '__MACOSX') !== false) {
                        continue;
                    }
                    
                    if (in_array($extension, $allowedExtensions)) {
                        $imageCount++;
                    }
                }
                
                $zip->close();
                
                if ($imageCount === 0) {
                    $validator->errors()->add('zip_upload', 'ZIP file must contain at least one valid image file (JPEG, PNG, GIF, or WebP).');
                }
                
                if ($imageCount > 200) {
                    $validator->errors()->add('zip_upload', 'ZIP file cannot contain more than 200 images.');
                }
                
            } else {
                $validator->errors()->add('zip_upload', 'Unable to read ZIP file. Please ensure it is a valid ZIP archive.');
            }
            
        } catch (\Exception $e) {
            $validator->errors()->add('zip_upload', 'Error validating ZIP file: ' . $e->getMessage());
        }
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Auto-generate slug if not provided
        if ($this->title && !$this->slug) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title)
            ]);
        }

        // Set default published_at for immediate publishing
        if (!$this->published_at && $this->get('publish_now')) {
            $this->merge([
                'published_at' => now()
            ]);
        }

        // Ensure is_premium is boolean
        $this->merge([
            'is_premium' => (bool) $this->get('is_premium', false)
        ]);
    }
}