<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Center;
use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\Course;
use App\Models\Halaqa;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'announcements' => $this->content('announcement')->where('featured', true)->limit(3)->get(),
            'news' => $this->content('news')->limit(3)->get(),
            'activities' => $this->content('activity')->limit(3)->get(),
            'achievements' => $this->content('achievement')->limit(4)->get(),
            'programs' => Course::query()->whereIn('status', ['planned', 'active'])->latest('starts_at')->limit(4)->get(),
            'stats' => Cache::remember('public-site:home-stats:v1', now()->addMinutes(5), fn () => [
                'students' => Student::query()->where('status', 'active')->count(),
                'halaqas' => Halaqa::query()->where('active', true)->count(),
                'programs' => Course::query()->count(),
                'achievements' => Achievement::query()->count(),
            ]),
        ]);
    }

    public function about(): View
    {
        return view('public.about', ['page' => $this->page('about'), 'center' => Center::query()->where('active', true)->first()]);
    }

    public function programs(): View
    {
        return view('public.programs', ['page' => $this->page('programs'), 'programs' => Course::query()->with('instructor.user:id,name')->latest('starts_at')->paginate(12)]);
    }

    public function activities(): View
    {
        return view('public.content-index', ['heading' => 'الأنشطة والفعاليات', 'eyebrow' => 'حياة المركز', 'description' => 'محطات تعليمية وتربوية تجمع طلاب القرآن.', 'items' => $this->content('activity')->paginate(12), 'routeName' => 'activities.show']);
    }

    public function activity(CmsContent $content): View
    {
        abort_unless($content->type === 'activity' && $content->status === 'published' && (! $content->published_at || $content->published_at->isPast()), 404);

        return view('public.content-show', ['content' => $content->load('featuredMedia'), 'section' => 'الأنشطة', 'backRoute' => 'activities.index']);
    }

    public function news(): View
    {
        return view('public.content-index', ['heading' => 'أخبار المركز', 'eyebrow' => 'آخر المستجدات', 'description' => 'تابع أخبار الحلقات والطلاب والبرامج.', 'items' => $this->content('news')->paginate(12), 'routeName' => 'news.show']);
    }

    public function newsItem(CmsContent $content): View
    {
        abort_unless($content->type === 'news' && $content->status === 'published' && (! $content->published_at || $content->published_at->isPast()), 404);

        return view('public.content-show', ['content' => $content->load('featuredMedia'), 'section' => 'الأخبار', 'backRoute' => 'news.index']);
    }

    public function achievements(): View
    {
        return view('public.achievements', ['items' => $this->content('achievement')->paginate(12), 'academicCount' => Achievement::query()->count()]);
    }

    public function gallery(): View
    {
        return view('public.gallery', ['media' => CmsMedia::query()->where('is_gallery', true)->where('kind', 'image')->orderBy('sort_order')->latest()->paginate(18)]);
    }

    public function contact(): View
    {
        return view('public.contact', ['center' => Center::query()->where('active', true)->first()]);
    }

    public function sitemap(): Response
    {
        $fixed = collect([
            route('public.home'), route('public.about'), route('public.programs'), route('activities.index'),
            route('news.index'), route('public.achievements'), route('public.gallery'), route('public.contact'),
        ])->map(fn (string $url) => ['url' => $url, 'updated_at' => now()]);
        $dynamic = CmsContent::query()->published()->whereIn('type', ['page', 'news', 'activity'])->get()->map(fn (CmsContent $content) => [
            'url' => match ($content->type) {
                'news' => route('news.show', $content),
                'activity' => route('activities.show', $content),
                default => in_array($content->slug, ['about', 'programs'], true) ? route('public.'.$content->slug) : route('public.page', $content),
            },
            'updated_at' => $content->updated_at,
        ]);

        return response()->view('public.sitemap', ['items' => $fixed->concat($dynamic)->unique('url')], 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /dashboard\nDisallow: /cms\nDisallow: /api/\nSitemap: ".route('public.sitemap')."\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function page(CmsContent|string $content): View|CmsContent|null
    {
        if ($content instanceof CmsContent) {
            abort_unless($content->type === 'page' && $content->status === 'published' && (! $content->published_at || $content->published_at->isPast()), 404);

            return view('public.content-show', ['content' => $content->load('featuredMedia'), 'section' => 'المركز', 'backRoute' => 'public.home']);
        }

        return $this->content('page')->where('slug', $content)->first();
    }

    private function content(string $type)
    {
        return CmsContent::query()->published()->ofType($type)->with('featuredMedia')->orderByDesc('featured')->latest('published_at');
    }
}
