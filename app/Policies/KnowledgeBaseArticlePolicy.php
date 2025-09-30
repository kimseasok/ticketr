<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;

class KnowledgeBaseArticlePolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->isAgent($user) || $this->isViewer($user);
    }

    public function view(User $user, KnowledgeBaseArticle $article): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->sharesTenant($user, $article->tenant_id)) {
            return false;
        }

        return $this->sharesBrand($user, $article->brand_id);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->isAgent($user);
    }

    public function update(User $user, KnowledgeBaseArticle $article): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $this->sharesTenant($user, $article->tenant_id)
            && $this->sharesBrand($user, $article->brand_id);
    }

    public function delete(User $user, KnowledgeBaseArticle $article): bool
    {
        return $this->update($user, $article);
    }
}
