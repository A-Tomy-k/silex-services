<?php

namespace Controllers;
 
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
 
class AndroidController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
 
		# User login
        $controllers
			->post('/login', array($this, 'login'))
			->before(array($this, 'jsonChecker'));
 
		# User registration
        $controllers
			->post('/register', array($this, 'register'))
			->before(array($this, 'jsonChecker'));
		
		# User question update for a certain genre
		$controllers
			->post('/update/{genre}/{points}', array($this, 'updateGenreScore'))
			->assert('points','0|1|2|3')
			->before(array($this, 'jsonChecker'));
		
		# Gets the next question of a certain genre
		$controllers
			->post('/question/{genre}', array($this, 'getNextQuestion'))
			->before(array($this, 'jsonChecker'));		
 
        return $controllers;
    }
 
	/**
		Checks if the user credentials given are correct
	*/
    public function login(Application $app, Request $request){
        
		$username = $request->request->get('username');
		$password = $request->request->get('password');
		
		$sql = "SELECT * FROM player WHERE username = ? && password = ?";
		$result = $app['db']->fetchAssoc($sql, array($username, $password));
		
		if($result){
			return new Response('OK',200);
		}
		else{
			$app->abort(403, 'Incorrect credentials');
		}
    }
 
	/**
		Adds a new user to the 'player' table
	*/
    public function register(Application $app, Request $request){
        
		$username = $request->request->get('username');
		$password = $request->request->get('password');
		$email = $request->request->get('email');
		
		$sql = "INSERT INTO player (username,password,email) VALUES(?,?,?)";
		$result = $app['db']->fetchAssoc($sql, array($username, $password, $email));
		
		if($result){
			return new Response('OK',201);
		}
		else{
			$app->abort(500, 'Error accessing to the database. Please, try again');
		}
    }
	
	/**
		Adds a row in 'results' table. User score is automatically updated by a trigger
	*/
	public function updateGenreScore(Application $app, Request $request, $genre, $points){
		
		$username = $request->request->get('username');
		$question = $request->request->get('level');
		
		$sql = "INSERT INTO results (username,level,genre,points) VALUES (?,?,?,?)";
		$result = $app['db']->fetchAssoc($sql, array($username, $level, $genre, $points));
		
		if($result){
			return new Response('OK',201);
		}
		else{
			$app->abort(500, 'Error accessing to the database. Please, try again');
		}
	}
	
	/**
		Returns the next question of the requested genre for the user
	*/
	public function getNextQuestion(Application $app, Request $request, $genre){
		
		$level = $request->request->get('level');
		
		$sql = "SELECT * FROM questions WHERE id = ? AND genre = ?";
		$result = $app['db']->fetchAssoc($sql, array($level, $genre));
		
		if($result){
			return $app->json($result);
		}
		else{
			$app->abort(404, 'There aren\'t more questions at the moment');
		}
	}
	
	/**
		Middleware function that checks if the request contains a JSONObject 
	*/
	public function jsonChecker(Request $request){
	
		if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
			$data = json_decode($request->getContent(), true);
			$request->request->replace(is_array($data) ? $data : null);
		}
	}
}