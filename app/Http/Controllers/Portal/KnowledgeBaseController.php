<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Brand $brand): View
    {
        $articles = KnowledgeBaseArticle::query()
            ->where('tenant_id', $brand->tenant_id)
            ->where(function ($query) use ($brand): void {
                $query->whereNull('brand_id')->orWhere('brand_id', $brand->id);
            })
            ->where('status', KnowledgeBaseArticle::STATUS_PUBLISHED)
            ->orderBy('title')
            ->get();

        return view('portal.knowledge-base.index', [
            'brand' => $brand,
            'articles' => $articles,
        ]);
    }

    public function show(Brand $brand, KnowledgeBaseArticle $article): View
    {
        abort_unless($article->status === KnowledgeBaseArticle::STATUS_PUBLISHED, 404);
        abort_if($article->tenant_id !== $brand->tenant_id, 404);
        abort_if($article->brand_id && $article->brand_id !== $brand->id, 404);

        return view('portal.knowledge-base.show', [
            'brand' => $brand,
            'article' => $article,
        ]);
    }
}
