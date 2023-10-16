<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Rating;
use App\Models\Wishlist;
use App\Models\Listing;
use App\Models\Reservation;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function index() {
        $listings = Listing::latest()->where('status', 'approved')->get();

        foreach($listings as $gh) {
            $ratings = Rating::where('listing_id', $gh->id)->get();
            $totalRatings = count($ratings);
            $sumRatings = $ratings->sum('rating');
            $averageRating = $totalRatings > 0 ? ($sumRatings / $totalRatings) : 0;
            $gh->averageRating = number_format($averageRating, 2);
        }

        return Inertia::render('Guest/Index', [
            'listings' => $listings
        ]);
    }

    public function show(Listing $listing) {

        $ratings = Rating::where('listing_id', $listing->id)
        ->whereIn('user_id', function($query) {
            $query->select('id')->from('users');
        })->get();
        $reservedDates = Reservation::where('listing_id', $listing->id)
            ->select('checkin', 'checkout')
            ->get();

        $owner = User::find($listing->owner_id);

        if(auth()->user()) {
            $wishlist = Wishlist::where('user_id', auth()->user()->id)->where('listing_id', $listing->id)->first();
            $rating = Rating::where('user_id', auth()->user()->id)->where('listing_id', $listing->id)->first();
            
            if($rating) {
                $rating->user = User::find(auth()->user()->id);
            }

            $totalRatings = count($ratings);
            $sumRatings = $ratings->sum('rating');
            $averageRating = $totalRatings > 0 ? ($sumRatings / $totalRatings) : 0;
            $averageRating = number_format($averageRating, 2);

            foreach($ratings as $r) {
                $r->user = User::find($r->user_id);
            }

            return Inertia::render('Guest/Show', [
                'listing' => $listing,
                'wishlist' => $wishlist, 
                'rated' => $rating,
                'owner' => $owner,
                'ratings' => $ratings,
                'averageRating' => $averageRating,
                'reservedDates' => $reservedDates
            ]);
            
        }
        else {

            $totalRatings = count($ratings);
            $sumRatings = $ratings->sum('rating');
            $averageRating = $totalRatings > 0 ? ($sumRatings / $totalRatings) : 0;
            $averageRating = number_format($averageRating, 2);

            foreach($ratings as $r) {
                $r->user = User::find($r->user_id);
            }

            return Inertia::render('Guest/Show', [
                'listing' => $listing, 
                'owner' => $owner,
                'ratings' => $ratings, 
                'averageRating' => $averageRating
            ]);
        }
    }

    public function confirm_reservation(Listing $listing, Request $request) {

        $request->validate([
            'guests' => 'required',
            'checkin' => 'required',
            'checkout' => 'required',
            'days' => 'required',
        ]);

        return Inertia::render('Guest/ConfirmReservation', [
            'listing' => $listing, 
            'guests' => $request->query('guests'),
            'checkin' => $request->query('checkin'),
            'checkout' => $request->query('checkout'),
            'days' => $request->query('days')
        ]);
    }

    public function settings() {
        return Inertia::render('Settings');
    }

    public function guidelines() {
        return Inertia::render('Guidelines');
    }

    public function rules() {
        return Inertia::render('Admin/RulesAndRegulations');
    }

    public function wishlist() {
        return Inertia::render('Wishlist');
    }

    public function about() {
        return Inertia::render('About');
    }

    public function notifications() {
        return Inertia::render('Guest/Notifications');
    }

    public function contact_us() {
        return Inertia::render('ContactUs');
    }

    public function profile(User $user) {
        $listings =  Listing::where('owner_id', $user->id)->select(['id', 'images', 'title', 'location'])->get();
        return Inertia::render('Profile', ['user' => $user, 'listings' => $listings]);
    }

    public function reservations() {
        $reservations = Reservation::where('user_id', auth()->user()->id)->get();
        foreach($reservations as $r) {
            $r->listing = Listing::find($r->listing_id);
        }
        return Inertia::render('Guest/Reservations', ['reservations' => $reservations]);
    }

    public function map() {
        return Inertia::render('Guest/Map', ['listings' => Listing::all()]);
    }

}
