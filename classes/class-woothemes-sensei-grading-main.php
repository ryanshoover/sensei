<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Grading Overview List Table Class
 *
 * All functionality pertaining to the Admin Grading Overview Data Table in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.3.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - build_data_array()
 * - load_stats()
 * - stats_boxes()
 * - no_items()
 * - data_table_header()
 * - data_table_footer()
 */
class WooThemes_Sensei_Grading_Main extends WooThemes_Sensei_List_Table {
	public $user_id;
	public $course_id;
	public $lesson_id;

	/**
	 * Constructor
	 * @since  1.3.0
	 * @return  void
	 */
	public function __construct ( $course_id = 0, $lesson_id = 0 ) {
		$this->course_id = intval( $course_id );
		$this->lesson_id = intval( $lesson_id );
		// Load Parent token into constructor
		parent::__construct( 'grading_main' );

		// Default Columns
		$this->columns = apply_filters( 'sensei_grading_main_columns', array(
			'user_login' => __( 'Learner', 'woothemes-sensei' ),
			'course' => __( 'Course', 'woothemes-sensei' ),
			'lesson' => __( 'Lesson', 'woothemes-sensei' ),
			'updated' => __( 'Updated', 'woothemes-sensei' ),
			'user_status' => __( 'Status', 'woothemes-sensei' ),
			'user_grade' => __( 'Grade', 'woothemes-sensei' ),
			'action' => __( '', 'woothemes-sensei' )
		) );
		// Sortable Columns
		$this->sortable_columns = apply_filters( 'sensei_grading_main_columns_sortable', array(
			'user_login' => array( 'user_login', false ),
			'course' => array( 'course', false ),
			'lesson' => array( 'lesson', false ),
			'updated' => array( 'updated', false ),
			'user_status' => array( 'user_status', false ),
			'user_grade' => array( 'user_grade', false )
		) );

		// Actions
		add_action( 'sensei_before_list_table', array( $this, 'data_table_header' ) );
		add_action( 'sensei_after_list_table', array( $this, 'data_table_footer' ) );
	} // End __construct()

	/**
	 * build_data_array builds the data for use in the table
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return array
	 */
	public function build_data_array() {
		global $woothemes_sensei;

		$return_array = array();
		$row_data = false;

		$args_array = array();
		// Handle Search
		if ( isset( $_POST['s'] ) && '' != esc_html( $_POST['s'] ) ) {
			$args_array['search'] = esc_html( $_POST['s'] );
		} // End If Statement
		if ( isset( $args_array['search'] ) && '' !== $args_array['search'] ) {
			$args_array['search'] = '*' . $args_array['search'] . '*';
		} // End If Statement
		// Get Users data

		$search = isset( $_REQUEST['s'] ) ? trim( $_REQUEST['s'] ) : '';

		$offset = '';
		if ( isset($_GET['paged']) && 0 < intval($_GET['paged']) ) {
			$offset = $this->per_page * ( $_GET['paged'] - 1 );
		} // End If Statement

		$output_counter = 0;

		if ( isset( $this->lesson_id ) && 0 < intval( $this->lesson_id ) ) {

			$lesson_id = $this->lesson_id;
			$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );

			$this->user_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => intval( $lesson_id ), 'type' => 'sensei_lesson_start', 'field' => 'user_id' ) );

			$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';

			$args_array = array(
				'number' => $this->per_page,
				'include' => $this->user_ids,
				'offset' => $offset,
				'role' => $role,
				'search' => $search,
				'fields' => 'all_with_meta'
			);
			if ( '' !== $args_array['search'] ) {
				$args_array['search'] = '*' . $args_array['search'] . '*';
			} // End If Statement

			$users = $this->user_query_results( $args_array );

			foreach ( $users as $user_key => $user_item ) {
				// Get row data
				$row_data = $this->row_data( $lesson_id, $user_item->ID );

				// Add row to table data
				if( $row_data ) {
					array_push( $return_array, $row_data );
				}
			}

		} else {
			$this->lesson_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'type' => 'sensei_lesson_start', 'field' => 'post_id' ) );

			if( isset( $this->lesson_ids ) && count( $this->lesson_ids ) > 0 ) {

				foreach( $this->lesson_ids as $lesson_id ) {

					// Get user ID
					$user_id = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => $lesson_id, 'type' => 'sensei_lesson_start', 'field' => 'user_id' ) );

					$user = get_userdata( $user_id[0] );

					$show_user = true;
					if( $search ) {
						$show_user = $this->user_search( $user, $search );
					}

					if( $show_user ) {

						// Get row data
						$row_data = $this->row_data( $lesson_id, $user_id[0] );

						// Add row to table data
						if( $row_data ) {
							array_push( $return_array, $row_data );
						}
					}
				}
			} // End If Statement

		} // End If Statement

		// Sort the data
		$return_array = $this->array_sort_reorder( $return_array );

		return $return_array;
	} // End build_data_array()

	/**
	 * Search a user object for a given string
	 * @since  1.5.0
	 * @param  object  $user   User object
	 * @param  string  $search String to search for
	 * @return boolean         True on success
	 */
	private function user_search( $user, $search ) {

	    if( stripos( $user->user_login, $search ) !== false ) {
	    	return true;
	    }

	    if( stripos( $user->display_name, $search ) !== false ) {
	    	return true;
	    }

	    if( stripos( $user->user_email, $search ) !== false ) {
	    	return true;
	    }

	    return false;
	}

	/**
	 * Fetch data for single table row
	 * @since  1.5.0
	 * @param  integer $lesson_id ID of lesson
	 * @param  integer $user_id   ID of user
	 * @return array              Data for table row
	 */
	private function row_data( $lesson_id, $user_id ) {
		global $woothemes_sensei;

		$grading_status = '';
		if( isset( $_GET['grading_status'] ) && strlen( $_GET['grading_status'] ) > 0 && $_GET['grading_status'] != 'all' ) {
			$grading_status = $_GET['grading_status'];
		}
		if( ! $grading_status && $_GET['grading_status'] != 'all' ) {
			$grading_status = 'ungraded';
		}

		// Get Quiz ID
		$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
	    foreach ( $lesson_quizzes as $quiz_item ) {
	    	$lesson_quiz_id = $quiz_item->ID;
	    }

	    // Get course ID
	    $course_id = get_post_meta( $lesson_id, '_lesson_course', true );

	    // Get user object
	    $user = get_userdata( $user_id );

		// Get Start Date
		$lesson_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );

		// Check if Lesson is complete
		$lesson_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_date' ) );

		// Quiz Grade
		$lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
		$quiz_grade = __( 'No Grade', 'woothemes-sensei' );
		if ( 0 < intval( $lesson_grade ) ) {
	    	$quiz_grade = $lesson_grade . '%';
	    } // End If Statement

	    $updated = '';
	    if ( ( isset( $lesson_end_date ) && '' != $lesson_end_date ) && ( isset( $lesson_grade ) && '' == $lesson_grade ) ) {
	    	if( $grading_status && $grading_status != 'ungraded' ) { return false; }
	    	$status = 'ungraded';
			$status_html = '<span class="ungraded">' . apply_filters( 'sensei_ungraded_text', __( 'Ungraded', 'woothemes-sensei' ) ) . '</span>';
			$updated = $lesson_end_date;
		} elseif ( isset( $lesson_grade ) && 0 < intval( $lesson_grade ) ) {
			if( $grading_status && $grading_status != 'graded' ) { return false; }
			$status = 'graded';
			$status_html = '<span class="graded">' . apply_filters( 'sensei_graded_text', __( 'Graded', 'woothemes-sensei' ) ) . '</span>';
			$updated =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_date' ) );
		} elseif ( ( isset( $lesson_start_date ) && '' != $lesson_start_date ) && ( isset( $lesson_end_date ) && '' == $lesson_end_date  ) ) {
			if( $grading_status && $grading_status != 'in-progress' ) { return false; }
			$status = 'in-progress';
			$status_html = '<span class="in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) . '</span>';
			$updated = $lesson_start_date;
		}  // End If Statement

		// Output the users data
		if ( isset( $lesson_start_date ) && '' != $lesson_start_date ) {

			$quiz_link = add_query_arg( array( 'page' => 'sensei_grading', 'user' => $user_id, 'quiz_id' => $lesson_quiz_id ), admin_url( 'admin.php' ) );
			switch( $status ) {
				case 'ungraded': $grade_link = '<a class="button-primary" href="' . $quiz_link . '">Grade quiz</a>'; break;
				case 'graded': $grade_link = '<a class="button-secondary" href="' . $quiz_link . '">Review grade</a>'; break;
				case 'in-progress': $grade_link = ''; break;
			}
			if( $status == 'ungraded' ) {
				$button_class = 'button-primary';
			} else {
				$button_class = 'button-secondary';
			}
			return apply_filters( 'sensei_grading_main_column_data', array(
					'user_login' => $user->display_name,
					'course' => get_the_title( $course_id ),
					'lesson' => get_the_title( $lesson_id ),
					'updated' => $updated,
					'user_status' => $status_html,
					'user_grade' => $quiz_grade,
					'action' => $grade_link,
			), $lesson_id, $user_id );

		} // End If Statement
	}

	/**
	 * load_stats loads stats into object
	 * @since  1.3.0
	 * @return void
	 */
	public function load_stats() {
		global $woothemes_sensei;
	} // End load_stats()

	/**
	 * stats_boxes loads which stats boxes to render
	 * @since  1.3.0
	 * @return $stats_to_render array of stats boxes and values
	 */
	public function stats_boxes () {
		$stats_to_render = array();
		return $stats_to_render;
	} // End stats_boxes

	/**
	 * no_items sets output when no items are found
	 * Overloads the parent method
	 * @since  1.3.0
	 * @return void
	 */
	public function no_items() {
		_e( 'No learners/quizzes found.', 'woothemes-sensei' );
	} // End no_items()

	/**
	 * data_table_header output for table heading
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_header() {
		global $woothemes_sensei;

		$all_class = $ungraded_class = $graded_class = $inprogress_class = '';
		if( ( isset( $_GET['grading_status'] ) && $_GET['grading_status'] == 'all' ) ) { $all_class = 'current'; }
		if( isset( $_GET['grading_status'] ) && $_GET['grading_status'] == 'ungraded' || ! isset( $_GET['grading_status'] ) ) { $ungraded_class = 'current'; }
		if( isset( $_GET['grading_status'] ) && $_GET['grading_status'] == 'graded' ) { $graded_class = 'current'; }
		if( isset( $_GET['grading_status'] ) && $_GET['grading_status'] == 'in-progress' ) { $inprogress_class = 'current'; }

		?>
		<div class="grading-selects">
			<?php

			$all_lessons_count = $ungraded_lessons_count = $graded_lessons_count = $inprogress_lessons_count = 0;
			$all_lessons_args = array( 'type' => 'sensei_lesson_start', 'field' => 'post_id' );
			if( isset( $_GET['lesson_id'] ) && intval( $_GET['lesson_id'] ) > 0 ) {
				$all_lessons_args['post_id'] = intval( $_GET['lesson_id'] );
			}
			$all_lessons = WooThemes_Sensei_Utils::sensei_activity_ids( $all_lessons_args );
			$all_lessons_count = count( $all_lessons );

			foreach( $all_lessons as $lesson_id ) {

				// Get Quiz ID
				$lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
			    foreach ( $lesson_quizzes as $quiz_item ) {
			    	$lesson_quiz_id = $quiz_item->ID;
			    }

				// Get user ID
				$user_id = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => $lesson_id, 'type' => 'sensei_lesson_start', 'field' => 'user_id' ) );

				// Get Start Date
				$lesson_start_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );

				// Check if Lesson is complete
				$lesson_end_date =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_id, 'user_id' => $user_id, 'type' => 'sensei_lesson_end', 'field' => 'comment_date' ) );

				// Quiz Grade
				$lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_date' ) );

				if ( ( isset( $lesson_end_date ) && '' != $lesson_end_date ) && ( isset( $lesson_grade ) && '' == $lesson_grade ) ) {
			    	++$ungraded_lessons_count;
				} elseif ( isset( $lesson_grade ) && 0 < intval( $lesson_grade ) ) {
					++$graded_lessons_count;
				} elseif ( ( isset( $lesson_start_date ) && '' != $lesson_start_date ) && ( isset( $lesson_end_date ) && '' == $lesson_end_date  ) ) {
					++$inprogress_lessons_count;
				}
			}

			echo '<ul class="subsubsub">' . "\n";

				echo '<li class="all"><a class="' . $all_class . '" href="' . add_query_arg( 'grading_status', 'all' ) . '">' . __( 'All', 'woothemes-sensei' ) . ' <span class="count">(' . $all_lessons_count . ')</span></a> | </li>';
				echo '<li class="ungraded"><a class="' . $ungraded_class . '" href="' . add_query_arg( 'grading_status', 'ungraded' ) . '">' . __( 'Ungraded', 'woothemes-sensei' ) . ' <span class="count">(' . $ungraded_lessons_count . ')</span></a> | </li>';
				echo '<li class="graded"><a class="' . $graded_class . '" href="' . add_query_arg( 'grading_status', 'graded' ) . '">' . __( 'Graded', 'woothemes-sensei' ) . ' <span class="count">(' . $graded_lessons_count . ')</span></a> | </li>';
				echo '<li class="in-progress"><a class="' . $inprogress_class . '" href="' . add_query_arg( 'grading_status', 'in-progress' ) . '">' . __( 'In Progress', 'woothemes-sensei' ) . ' <span class="count">(' . $inprogress_lessons_count . ')</span></a></li>';

			echo '</ul>' . "\n";

			// Get the Course Posts
			$post_args = array(	'post_type' 		=> 'course',
								'numberposts' 		=> -1,
								'orderby'         	=> 'title',
	    						'order'           	=> 'DESC',
	    						'post_status'      	=> 'any',
	    						'suppress_filters' 	=> 0
								);
			$posts_array = get_posts( $post_args );

			$selected_course_id = 0;
			if ( isset( $_GET['course_id'] ) ) {
				$selected_course_id = intval( $_GET['course_id'] );
			} // End If Statement

			echo '<div class="select-box">' . "\n";

				echo '<select id="grading-course-options" name="grading_course" class="widefat">' . "\n";
					echo '<option value="">' . __( 'Select a course', 'woothemes-sensei' ) . '</option>';
					if ( count( $posts_array ) > 0 ) {
						foreach ($posts_array as $post_item){
							echo '<option value="' . esc_attr( absint( $post_item->ID ) ) . '" ' . selected( $post_item->ID, $selected_course_id, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
						} // End For Loop
					} // End If Statement
				echo '</select>' . "\n";

			echo '</div>' . "\n";

			echo '<div class="select-box">' . "\n";

				echo '<select id="grading-lesson-options" data-placeholder="&larr; Select a course" name="grading_lesson" class="widefat">' . "\n";

					if ( 0 < $selected_course_id ) {
						$selected_lesson_id = 0;
						if ( isset( $_GET['lesson_id'] ) ) {
							$selected_lesson_id = intval( $_GET['lesson_id'] );
						} // End If Statement
						echo $woothemes_sensei->grading->lessons_drop_down_html( $selected_course_id, $selected_lesson_id );
					} // End If Statement

				echo '</select>' . "\n";

			echo '</div>' . "\n";
			?>
		</div><!-- /.grading-selects -->
		<?php
	} // End data_table_header()

	/**
	 * data_table_footer output for table footer
	 * @since  1.3.0
	 * @return void
	 */
	public function data_table_footer() {
		// Nothing right now
	} // End data_table_footer()

} // End Class
?>