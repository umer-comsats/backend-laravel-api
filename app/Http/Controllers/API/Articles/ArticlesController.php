<?php

namespace App\Http\Controllers\API\Articles;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ArticlesController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $source = $user->preferred_source ?? '';
        $category = $user->preferred_category ?? '';
        $author = $user->preferred_author ?? '';

        $keyword = '';
        $date = '';

        if ($author !== '') {
            $newsApiArticles = $this->fetchNewsApiArticles($keyword, $date, $category, $source, $author);
            $newsApiArticles = $this->cleanNewsApiArticles($newsApiArticles);
            return response()->json($newsApiArticles);
        } else {
            // Fetch articles from NewsAPI
            $newsApiArticles = $this->fetchNewsApiArticles($keyword, $date, $category, $source);

            // Fetch articles from New York Times
            $nytArticles = $this->fetchNYTArticles($keyword, $date, $category, $source);

            // Fetch articles from The Guardian
            $guardianArticles = $this->fetchGuardianArticles($keyword, $date, $category, $source);

            // Clean the articles to have consistent attributes
            $newsApiArticles = $this->cleanNewsApiArticles($newsApiArticles);
            $nytArticles = $this->cleanNYTArticles($nytArticles);
            $guardianArticles = $this->cleanGuardianArticles($guardianArticles);

            // Combine all the articles into a single array
            $articles = array_merge($newsApiArticles, $nytArticles, $guardianArticles);

            // Return the articles as a JSON response
            return response()->json($articles);
        }
    }

    public function filter(Request $request)
    {
        // Get the filter parameters from the request
        $keyword = $request->input('keyword');
        $date = $request->input('date');
        $category = $request->input('category');
        $source = $request->input('source');

        // Fetch articles from NewsAPI with applied filters
        $newsApiArticles = $this->fetchNewsApiArticles($keyword, $date, $category, $source);

        // Fetch articles from New York Times with applied filters
        $nytArticles = $this->fetchNYTArticles($keyword, $date, $category, $source);

        // Fetch articles from The Guardian with applied filters
        $guardianArticles = $this->fetchGuardianArticles($keyword, $date, $category, $source);

        // Clean the articles to have consistent attributes
        $newsApiArticles = $this->cleanNewsApiArticles($newsApiArticles);
        $nytArticles = $this->cleanNYTArticles($nytArticles);
        $guardianArticles = $this->cleanGuardianArticles($guardianArticles);

        // Combine all the articles into a single array
        $articles = array_merge($newsApiArticles, $nytArticles, $guardianArticles);

        // Return the filtered articles as a JSON response
        return response()->json($articles);
    }


    private function fetchNewsApiArticles($keyword = 'general', $date = '', $category = '', $source = '', $author = '')
    {
        if (empty($keyword)) {
            $keyword = 'general';
        }
        // Build the query parameters based on the filter values
        $queryParams = [
            'q' => $keyword,
            'apiKey' => env('NEWS_API_KEY'),
        ];

        // Add additional filter parameters if provided
        if (!empty($date)) {
            $queryParams['date'] = $date;
        }
        if (!empty($category)) {
            $queryParams['category'] = $category;
        }
        if (!empty($source)) {
            $queryParams['sources'] = $source;
        }
        if (!empty($author)) {
            $queryParams['author'] = $author;
        }

        $client = new Client();
        $response = $client->get('https://newsapi.org/v2/everything', [
            'query' => $queryParams,
        ]);

        $data = json_decode($response->getBody(), true);
        // Extract the articles from the response data and return them
        return $data['articles'] ?? [];
    }

    private function fetchNYTArticles($keyword = '', $date = '', $category = '', $source = '')
    {
        $period = 1; // 1, 7, or 30

        // Build the query parameters based on the filter values
        $queryParams = [
            'api-key' => env('NYT_API_KEY'),
        ];

        $client = new Client();
        $response = $client->get('https://api.nytimes.com/svc/mostpopular/v2/viewed/' . $period . '.json', [
            'query' => $queryParams,
        ]);

        $data = json_decode($response->getBody(), true);
        // Extract the articles from the response data and return them
        return $data['results'] ?? [];
    }

    private function fetchGuardianArticles($keyword = '', $date = '', $category = '', $source = '')
    {
        // Build the query parameters based on the filter values
        $queryParams = [
            'api-key' => env('GUARDIAN_API_KEY'),
        ];

        // Add additional filter parameters if provided
        if (!empty($keyword)) {
            $queryParams['q'] = $keyword;
        }
        if (!empty($date)) {
            $queryParams['from-date'] = $date;
            $queryParams['to-date'] = $date; // Assuming only one specific date filter
        }
        if (!empty($category)) {
            $queryParams['section'] = $category;
        }
        if (!empty($source)) {
            $queryParams['source'] = $source;
        }

        $client = new Client();
        $response = $client->get('https://content.guardianapis.com/search', [
            'query' => $queryParams,
        ]);

        $data = json_decode($response->getBody(), true);
        // Extract the articles from the response data and return them
        return $data['response']['results'] ?? [];
    }


    private function cleanNewsApiArticles($articles)
    {
        // Clean the articles to have consistent attributes
        $cleanedArticles = [];
        foreach ($articles as $article) {
            $cleanedArticle = [
                'source' => $article['source']['name'] ?? '',
                'author' => $article['author'] ?? '',
                'title' => $article['title'] ?? '',
                'description' => $article['description'] ?? '',
                'content' => $article['content'] ?? '',
                'url' => $article['url'] ?? '',
                'imageUrl' => $article['urlToImage'] ?? '',
                'publishedAt' => $article['publishedAt'] ?? '',
            ];
            $cleanedArticles[] = $cleanedArticle;
        }
        return $cleanedArticles;
    }

    private function cleanNYTArticles($articles)
    {
        // Clean the articles to have consistent attributes
        $cleanedArticles = [];
        foreach ($articles as $article) {
            $cleanedArticle = [
                'source' => 'New York Times',
                'author' => $article['byline'] ?? '',
                'title' => $article['title'] ?? '',
                'description' => $article['abstract'] ?? '',
                'content' => $article['abstract'] ?? '',
                'url' => $article['url'] ?? '',
                'imageUrl' => $article['media'][0]['media-metadata'][0]['url'] ?? '',
                'publishedAt' => $article['published_date'] ?? '',
            ];
            $cleanedArticles[] = $cleanedArticle;
        }
        return $cleanedArticles;
    }

    private function cleanGuardianArticles($articles)
    {
        // Clean the articles to have consistent attributes
        $cleanedArticles = [];
        foreach ($articles as $article) {
            $cleanedArticle = [
                'source' => 'The Guardian',
                'author' => $article['fields']['byline'] ?? '',
                'title' => $article['webTitle'] ?? '',
                'description' => $article['webUrl'] ?? '',
                'url' => $article['webUrl'] ?? '',
                'imageUrl' => $article['fields']['thumbnail'] ?? '',
                'publishedAt' => $article['webPublicationDate'] ?? '',
            ];
            $cleanedArticles[] = $cleanedArticle;
        }
        return $cleanedArticles;
    }
}
