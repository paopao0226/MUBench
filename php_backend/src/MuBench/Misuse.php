<?php

namespace MuBench;


class ReviewState
{
    const NOTHING_TO_REVIEW = 0;
    const NEEDS_REVIEW = 1;
    const NEEDS_CLARIFICATION = 2;
    const DISAGREEMENT = 3;
    const AGREEMENT_YES = 4;
    const AGREEMENT_NO = 5;
    const RESOLVED_YES = 6;
    const RESOLVED_NO = 7;
}


class Decision
{
    const NO = 0;
    const MAYBE = 1;
    const YES = 2;
}


class Misuse
{
    public $id;

    private $data;
    private $potential_hits;
    private $reviews;

    /**
     * @param array $data
     * @param array $potential_hits
     * @param Review[] $reviews
     */
    public function __construct(array $data, array $potential_hits, $reviews)
    {
        assert(array_key_exists("misuse", $data), "misuse requires id");
        $this->id = $data["misuse"];
        $this->data = $data;
        $this->potential_hits = $potential_hits;
        $this->reviews = $reviews;
    }

    public function getProject()
    {
        if(!array_key_exists("project", $this->data)) return $this->potential_hits[0]["project"];
        return $this->data["project"];
    }

    public function getVersion()
    {
        if(!array_key_exists("version", $this->data)) return $this->potential_hits[0]["version"];
        return $this->data["version"];
    }

    public function getDescription()
    {
        return $this->data['description'];
    }

    public function getFixDescription()
    {
        return $this->data['fix_description'];
    }

    public function hasDiff()
    {
        return !empty($this->data['diff_url']);
    }

    public function getDiff()
    {
        return $this->data['diff_url'];
    }

    public function getShortId()
    {
        $project = $this->getProject();
        return substr($this->id, 0, strlen($project)) === $project ? substr($this->id, strlen($project) + 1) :
            $this->id;
    }

    public function getFile(){
        if(!array_key_exists("file", $this->data)) return $this->potential_hits[0]["file"];
        return $this->data['file'];
    }

    public function getMethod(){
        if(!array_key_exists("method", $this->data)) return $this->potential_hits[0]["method"];
        return $this->data['method'];
    }

    public function hasViolationTypes()
    {
        return !empty($this->getViolationTypes());
    }

    public function getViolationTypes()
    {
        return explode(";", $this->data["violation_types"]);
    }

    public function getCode(){
        return $this->data['snippets'];
    }

    public function getPatterns(){
        return $this->data['patterns'];
    }

    public function hasPatterns(){
        return !empty($this->getPatterns());
    }

    public function hasCode(){
        return !empty($this->getCode());
    }

    public function hasPotentialHits()
    {
        return !empty($this->getPotentialHits());
    }

    public function getPotentialHits()
    {
        return $this->potential_hits;
    }

    public function hasSnippets()
    {
        return !empty($this->getSnippets());
    }

    public function getSnippets()
    {
        return $this->data['snippets'];
    }

    public function hasReviewed($reviewer_name)
    {
        return $this->getReview($reviewer_name) !== NULL;
    }

    public function getReview($reviewer_name)
    {
        foreach ($this->reviews as $review) {
            if (strcmp($review->getReviewerName(), $reviewer_name) === 0) return $review;
        }
        return NULL;
    }

    public function getReviews()
    {
        return array_filter($this->reviews, function ($review) {
            return strcmp($review->getReviewerName(), "resolution") !== 0;
        });
    }

    public function hasSufficientReviews()
    {
        return $this->getReviewState() > ReviewState::NEEDS_REVIEW;
    }

    public function getReviewState()
    {
        if (!$this->hasPotentialHits()) {
            return ReviewState::NOTHING_TO_REVIEW;
        } elseif (count($this->getReviews()) < 2) {
            return ReviewState::NEEDS_REVIEW;
        } else {
            $decisions = [];
            $byResolution = $this->hasResolutionReview();
            if ($byResolution) {
                $decisions[$this->getResolutionReview()->getDecision()] = true;
            } else {
                foreach ($this->getReviews() as $review) {
                    $decisions[$review->getDecision()] = true;
                }
            }
            if (array_key_exists(Decision::MAYBE, $decisions)) {
                return ReviewState::NEEDS_CLARIFICATION;
            } elseif (count($decisions) > 1) {
                return ReviewState::DISAGREEMENT;
            } elseif (array_key_exists(Decision::YES, $decisions)) {
                return $byResolution ? ReviewState::RESOLVED_YES : ReviewState::AGREEMENT_YES;
            } else {
                return $byResolution ? ReviewState::RESOLVED_NO : ReviewState::AGREEMENT_NO;
            }
        }
    }

    private function hasResolutionReview()
    {
        return $this->hasReviewed("resolution");
    }

    private function getResolutionReview()
    {
        return $this->getReview("resolution");
    }

}