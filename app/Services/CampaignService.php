<?php

namespace Rogue\Services;

use Rogue\Models\Post;
use Rogue\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Finds a single campaign in Rogue.
     *
     * @param  string $id
     * @return Campaign
     */
    public function find($id)
    {
        return Campaign::find($id);
    }

    /**
     * Finds a group of campaigns in Rogue/Ashes.
     *
     * @param  array $ids
     * @return \Illuminate\Support\Collection
     */
    public function findAll($ids = [])
    {
        return $ids ? Campaign::find($ids) : Campaign::all();
    }

    /**
     * Group a collection of campaigns by cause space.
     *
     * @param  array $campaigns
     * @return \Illuminate\Support\Collection
     */
    public function groupByCause($campaigns)
    {
        return $campaigns->groupBy('cause')->toArray();
    }

    /**
     * Get a distinct set of campaign ids from the signups table.
     *
     * @return array|null
     */
    public function getCampaignIdsFromSignups()
    {
        $campaigns = DB::table('signups')->distinct()->select('campaign_id')->get();

        $ids = collect($campaigns)->pluck('campaign_id')->toArray();

        return $ids ? $ids : null;
    }

    /**
     * Gets the count of pending, accepted, and rejected stautses on each post for a single campaign.
     *
     * @param  array $campaign
     * @return \Illuminate\Support\Collection
     */
    public function getPostTotals($campaign)
    {
        return Post::select(
                'campaign_id',
                DB::raw('SUM(status = "accepted") as accepted_count'),
                DB::raw('SUM(status = "pending") as pending_count'),
                DB::raw('SUM(status = "rejected") as rejected_count'))
            ->whereReviewable()
            ->where('campaign_id', '=', $campaign['id'])
            ->first();
    }

    /**
     * Gets the count of pending stautses on each post for a collection of campaigns.
     *
     * @param  \Illuminate\Support\Collection $campaigns
     * @return \Illuminate\Support\Collection
     */
    public function getPendingPostTotals($campaigns)
    {
        $ids = $campaigns->pluck('id')->filter()->toArray();

        $totals = Post::selectRaw('campaign_id, count(id) as pending_count')
                    ->where('status', '=', 'pending')
                    ->whereReviewable()
                    ->wherein('campaign_id', $ids)
                    ->groupBy('campaign_id')
                    ->get();

        return $totals ? collect($totals)->keyBy('campaign_id') : collect();
    }

    /**
     * Appends count of pending posts to a collection of campaigns.
     *
     * @param  array $campaigns
     * @return \Illuminate\Support\Collection|null
     */
    public function appendPendingCountsToCampaigns($campaigns)
    {
        $campaignsWithCounts = $this->getPendingPostTotals($campaigns);

        if ($campaignsWithCounts) {
            $campaigns = $campaigns->map(function ($campaign, $key) use ($campaignsWithCounts) {
                if ($campaign) {
                    $statusCounts = $campaignsWithCounts->get($campaign['id']);

                    $campaign['pending_count'] = $statusCounts ? (int) $statusCounts->pending_count : 0;
                }

                return $campaign;
            });

            return $campaigns;
        }

        return null;
    }

    /**
     * Appends status counts to a collection of campaigns.
     *
     * @param  array $campaigns
     * @return \Illuminate\Support\Collection|null
     */
    public function appendStatusCountsToCampaigns($campaigns)
    {
        $campaignsWithCounts = $this->getPostTotals($campaigns);

        if ($campaignsWithCounts) {
            $campaigns = $campaigns->map(function ($campaign, $key) use ($campaignsWithCounts) {
                if ($campaign) {
                    $statusCounts = $campaignsWithCounts->get($campaign['id']);

                    if ($statusCounts) {
                        $campaign['accepted_count'] = (int) $statusCounts->accepted_count;
                        $campaign['pending_count'] = (int) $statusCounts->pending_count;
                        $campaign['rejected_count'] = (int) $statusCounts->rejected_count;
                    }
                }

                return $campaign;
            });

            return $campaigns;
        }

        return null;
    }
}
