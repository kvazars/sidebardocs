<?php

namespace App\Services;

use App\Models\About;
use App\Models\Available;
use App\Models\Content;
use App\Models\Tree;
use App\Models\User;
use App\Models\UserGroups;

class UserBootstrapService
{
    protected array $fatherChild = [];

    public function buildForUser(User $user): array
    {
        $role = $user->role;
        $about = About::first();
        $content = Content::first();
        $tree = null;

        switch ($role) {
            case 'ceo':
                $tree = Tree::where('user_id', $user->id)->get();
                $managersFiles = [];
                $cd = Content::where('accessibilitymanagers', true)->pluck('tree_id')->toArray();

                foreach ($cd as $key => $treeId) {
                    if (Tree::where('id', $treeId)->where('user_id', $user->id)->exists()) {
                        unset($cd[$key]);
                    }
                }

                $hiddenByLink = Content::where('accessibilitylink', true)->pluck('tree_id')->toArray();
                $cd = array_values(array_diff($cd, $hiddenByLink));

                if (count($cd) > 0) {
                    $all = array_merge($cd, $this->uploadTree($cd));
                    $sourceTree = Tree::whereIn('id', $all)
                        ->whereIn('user_id', User::pluck('id')->toArray())
                        ->get();
                    $users = User::whereIn('id', $sourceTree->pluck('user_id')->toArray())
                        ->get()
                        ->keyBy('id');
                    $userArray = [];

                    foreach ($users as $manager) {
                        $userArray[] = [
                            'id' => 'user_' . $manager->id,
                            'user_id' => $manager->id,
                            'name' => $manager->name,
                            'type' => 'folder',
                            'tree_id' => null,
                        ];
                    }

                    $newCollection = collect($sourceTree)->map(function ($item) use ($users) {
                        $item['tree_id'] = $item['tree_id'] ?: 'user_' . $users[$item->user_id]['id'];
                        return $item;
                    });

                    $managersFiles = array_merge($userArray, $newCollection->toArray());
                }

                $tree = array_merge($tree->toArray(), $managersFiles);
                $fileIds = array_column(
                    array_filter($tree, fn($item) => ($item['type'] ?? null) === 'file'),
                    'id'
                );
                break;

            case 'admin':
                $tree = Tree::whereIn('user_id', User::pluck('id')->toArray())->get();
                $users = User::whereIn('id', $tree->pluck('user_id')->toArray())->get()->keyBy('id');
                $userArray = [];

                foreach ($users as $manager) {
                    $userArray[] = [
                        'id' => 'user_' . $manager->id,
                        'name' => $manager->name,
                        'type' => 'folder',
                        'tree_id' => null,
                    ];
                }

                $newCollection = collect($tree)->map(function ($item) use ($users) {
                    $item['tree_id'] = $item['tree_id'] ?: 'user_' . $users[$item->user_id]['id'];
                    return $item;
                });

                $tree = array_merge($userArray, $newCollection->toArray());
                $fileIds = array_column(
                    array_filter($tree, fn($item) => ($item['type'] ?? null) === 'file'),
                    'id'
                );
                break;

            case 'user':
                $group = UserGroups::where('user_id', $user->id)->first('group_id');
                $availableTreeIds = [];

                if ($group) {
                    $availableTreeIds = Available::where('group_id', $group->group_id)
                        ->pluck('tree_id')
                        ->toArray();
                }

                $publicTreeIds = Content::where('accessibility', true)->pluck('tree_id')->toArray();
                $ownTreeIds = Tree::where('user_id', $user->id)->pluck('id')->toArray();
                $hiddenByLink = Content::where('accessibilitylink', true)
                    ->whereNotIn('tree_id', $ownTreeIds)
                    ->pluck('tree_id')
                    ->toArray();

                $tree = [];
                $availableTreeIds = array_values(
                    array_diff(array_merge($availableTreeIds, $publicTreeIds), $hiddenByLink)
                );

                if (count($availableTreeIds) > 0) {
                    $all = array_merge($availableTreeIds, $this->uploadTree($availableTreeIds));
                    $sourceTree = Tree::whereIn('id', $all)
                        ->whereIn('user_id', User::pluck('id')->toArray())
                        ->get();
                    $users = User::whereIn('id', $sourceTree->pluck('user_id')->toArray())
                        ->get()
                        ->keyBy('id');
                    $userArray = [];

                    foreach ($users as $manager) {
                        $userArray[] = [
                            'id' => 'user_' . $manager->id,
                            'name' => $manager->name,
                            'type' => 'folder',
                            'tree_id' => null,
                        ];
                    }

                    $newCollection = collect($sourceTree)->map(function ($item) use ($users) {
                        $item['tree_id'] = $item['tree_id'] ?: 'user_' . $users[$item->user_id]['id'];
                        return $item;
                    });

                    $tree = array_merge($userArray, $newCollection->toArray());
                }

                $fileIds = array_column(
                    array_filter($tree, fn($item) => ($item['type'] ?? null) === 'file'),
                    'id'
                );
                break;

            default:
                $tree = [];
                $fileIds = [];
                break;
        }

        $linkOnlyTreeIds = Content::where('accessibilitylink', true)->pluck('tree_id')->toArray();
        $tree = is_array($tree) ? $tree : $tree->toArray();
        $tree = array_map(function ($item) use ($linkOnlyTreeIds) {
            if (($item['type'] ?? null) === 'file') {
                $item['accessibilitylink'] = in_array($item['id'], $linkOnlyTreeIds);
            }

            return $item;
        }, $tree);

        return [
            'success' => true,
            'allId' => $fileIds,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $role,
            ],
            'menu' => $tree,
            'about' => $about,
            'content' => $content,
        ];
    }

    protected function uploadTree(array $childTree): array
    {
        $this->fatherChild = [];
        $this->collectParentIds($childTree);

        return $this->fatherChild;
    }

    protected function collectParentIds(array $childTree): void
    {
        foreach ($childTree as $treeId) {
            $tree = Tree::find($treeId);

            if (!$tree) {
                continue;
            }

            if ($tree->tree_id) {
                $this->fatherChild[] = $tree->tree_id;
                $this->collectParentIds([$tree->tree_id]);
            } else {
                $this->fatherChild[] = 0;
            }
        }
    }
}
