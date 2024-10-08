<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $quizes = Quiz::all();
        $userQuizes = $user->quizes;
        $completedQuizes = [];

        foreach ($userQuizes as $userQuiz) {
            $completedQuizes[$userQuiz->id] = [
                'score' => $userQuiz->pivot->score,
                'completed_at' => $userQuiz->pivot->completed_at
            ];
        }

        return view('quizes.index')
            ->with('quizes', $quizes)
            ->with('user', $user)
            ->with('completedQuizes', $completedQuizes);
        }

    public function show(string $id)
    {
        $quiz = Quiz::findOrFail($id);
        $questions = $quiz->questions;
        $categories = [];
        foreach ($questions as $question) {
            //check which categories belong to the quiz in pivot table question_category and add to $categories
            if ($question->categories->count() > 0) {
                foreach ($question->categories as $category) {
                    $categories[$category->id] = $category->name;
                }
            }
        }
        return view('quizes.show')
            ->with('quiz', $quiz)
            ->with('categories', $categories);
    }

    public function play(string $id)
    {
        $quiz = Quiz::findOrFail($id);
        return view('quizes.play')->with('quiz', $quiz);
    }

    public function checkMultiple(string $id)
    {
        $quiz = Quiz::with('questions.answers')->findOrFail($id);
        $questions = $quiz->questions;
        $answers = request()->except('_token');

        $score = 0;
        $answerComplete = [];

        foreach($questions as $question) {
            $submittedAnswerId = $answers[$question->id] ?? null;

            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            if ($submittedAnswerId) {
                $submittedAnswer = $question->answers->find($submittedAnswerId);
                $answerComplete[$question->id] = $submittedAnswer;

                if ($correctAnswer && $submittedAnswerId == $correctAnswer->id) {
                    $score++;
                }
            }
        }

        $totalQuestions = $questions->count();
        $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100, 1) : 0;

        $quiz->users()->attach(auth()->user(), ['score' => $score, 'completed_at' => now()]);

        return view('quizes.result', [
            'score' => $score,
            'total' => $totalQuestions,
            'percentage' => $percentage,
            'quiz' => $quiz,
            'questions' => $questions,
            'answerComplete' => $answerComplete
        ]);
    }
}
