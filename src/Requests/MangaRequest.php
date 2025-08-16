<?php

namespace Ophim\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MangaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|max:512',
            'original_title' => 'nullable|max:512',
            'slug' => 'max:255|unique:mangas,slug,' . $this->id . ',id',
            'type' => 'required|in:manga,manhwa,manhua,webtoon',
            'status' => 'required|in:ongoing,completed,hiatus,cancelled',
            'demographic' => 'required|in:shounen,seinen,josei,shoujo,kodomomuke,general',
            'reading_direction' => 'required|in:ltr,rtl,vertical',
            'publication_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'total_chapters' => 'nullable|integer|min:0',
            'total_volumes' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:10',
            'cover_image' => 'nullable|string|max:500',
            'banner_image' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'other_name' => 'nullable|string',
            'is_completed' => 'boolean',
            'is_recommended' => 'boolean',
            'is_adult_content' => 'boolean',
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
            'title' => 'Tên manga',
            'original_title' => 'Tên gốc',
            'slug' => 'Đường dẫn tĩnh',
            'type' => 'Loại manga',
            'status' => 'Tình trạng',
            'demographic' => 'Đối tượng',
            'reading_direction' => 'Hướng đọc',
            'publication_year' => 'Năm xuất bản',
            'total_chapters' => 'Tổng số chương',
            'total_volumes' => 'Tổng số tập',
            'rating' => 'Đánh giá',
            'cover_image' => 'Ảnh bìa',
            'banner_image' => 'Ảnh banner',
            'description' => 'Mô tả',
            'other_name' => 'Tên khác',
            'is_completed' => 'Đã hoàn thành',
            'is_recommended' => 'Đề cử',
            'is_adult_content' => 'Nội dung người lớn',
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
            'title.required' => 'Tên manga là bắt buộc.',
            'title.max' => 'Tên manga không được vượt quá 512 ký tự.',
            'slug.unique' => 'Đường dẫn tĩnh đã tồn tại.',
            'type.required' => 'Loại manga là bắt buộc.',
            'type.in' => 'Loại manga không hợp lệ.',
            'status.required' => 'Tình trạng là bắt buộc.',
            'status.in' => 'Tình trạng không hợp lệ.',
            'demographic.required' => 'Đối tượng là bắt buộc.',
            'demographic.in' => 'Đối tượng không hợp lệ.',
            'reading_direction.required' => 'Hướng đọc là bắt buộc.',
            'reading_direction.in' => 'Hướng đọc không hợp lệ.',
            'publication_year.integer' => 'Năm xuất bản phải là số nguyên.',
            'publication_year.min' => 'Năm xuất bản không được nhỏ hơn 1900.',
            'publication_year.max' => 'Năm xuất bản không được lớn hơn năm hiện tại.',
            'rating.numeric' => 'Đánh giá phải là số.',
            'rating.min' => 'Đánh giá không được nhỏ hơn 0.',
            'rating.max' => 'Đánh giá không được lớn hơn 10.',
        ];
    }
}