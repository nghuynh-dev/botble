<?php

use Botble\Base\Facades\MetaBox;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Rules\MediaImageRule;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\Member\Forms\PostForm as MemberPostForm;
use Botble\Page\Models\Page;
use Botble\Theme\Supports\ThemeSupport;
use Botble\Widget\Events\RenderingWidgetSettings;
use Illuminate\Routing\Events\RouteMatched;

app()->booted(function () {
    RvMedia::addSize('featured', 565, 375)
        ->addSize('medium', 540, 360);
});

app('events')->listen(RouteMatched::class, function () {
    ThemeSupport::registerSocialLinks();
    ThemeSupport::registerToastNotification();
    ThemeSupport::registerPreloader();
    ThemeSupport::registerSiteCopyright();

    register_page_template([
        'no-sidebar' => __('No sidebar'),
    ]);

    app('events')->listen(RenderingWidgetSettings::class, function () {
        register_sidebar([
            'id' => 'top_sidebar',
            'name' => __('Top sidebar'),
            'description' => __('Area for widgets on the top sidebar'),
        ]);

        register_sidebar([
            'id' => 'footer_sidebar',
            'name' => __('Footer sidebar'),
            'description' => __('Area for footer widgets'),
        ]);
    });

    FormAbstract::extend(function (FormAbstract $form): void {
        $model = $form->getModel();

        if (! $model instanceof Post && ! $model instanceof Page) {
            return;
        }

        $form
            ->addAfter(
                'image',
                'banner_image',
                MediaImageField::class,
                MediaImageFieldOption::make()->label(__('Banner image (1920x170px)'))->metadata()->toArray()
            );
    }, 124);

    FormAbstract::afterSaving(function (FormAbstract $form): void {
        if (! $form instanceof MemberPostForm) {
            return;
        }

        $request = $form->getRequest();

        $request->validate([
            'banner_image_input' => ['nullable', new MediaImageRule()],
        ]);

        if ($request->hasFile('banner_image_input')) {
            $result = RvMedia::handleUpload($request->file('banner_image_input'), 0, 'members');

            if (! $result['error']) {
                MetaBox::saveMetaBoxData($form->getModel(), 'banner_image', $result['data']->url);
            }
        }
    }, 175);
});
